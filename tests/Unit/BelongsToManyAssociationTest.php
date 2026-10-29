<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\Buttons\Button;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelAssociatorHelper;
use IlBronza\CRUD\Http\Controllers\Utilities\Relationships\AssociateBelongsToManyController;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;
use IlBronza\CRUD\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BelongsToManyAssociationTest extends TestCase
{
	protected function setUp() : void
	{
		parent::setUp();

		Relation::morphMap([
			'association-parent' => AssociationParent::class,
		]);
	}

	public function test_belongs_to_many_can_render_an_associate_button() : void
	{
		$parent = new AssociationParent();
		$parent->setRawAttributes(['id' => 42], true);
		$parent->exists = true;

		$manager = new class('show', $parent) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return [
					'show' => [
						'relations' => [
							'roles' => [
								'hasAssociateButton' => true,
								'associateButtonText' => 'Associa ruoli',
								'fieldsGroups' => ['base' => ['fields' => []]],
							],
						],
					],
				];
			}
		};

		$relationship = $manager->getRelationships()->get('roles');
		$table = new AssociationButtonsTable();
		$relationship->table = $table;
		$relationship->manageTableButtons();

		$this->assertCount(1, $table->buttons);
		$this->assertSame('Associa ruoli', $table->buttons[0]->getText());
		$this->assertTrue($table->buttons[0]->isIframe());
		$this->assertTrue($table->buttons[0]->isAjax());
		$this->assertSame('GET', $table->buttons[0]->getData()['method']);
		$this->assertStringContainsString('crud/relationships/model/association-parent/', $table->buttons[0]->getHref());
		$this->assertTrue($relationship->createAssociateTable()->hasSelectRowCheckboxes());
	}

	public function test_picker_only_associates_selected_new_models_without_detaching_existing_ones() : void
	{
		$this->createAssociationTables();

		$parent = AssociationParent::create(['name' => 'Parent']);
		$existingRole = AssociationRole::create(['name' => 'Already associated']);
		$selectedRole = AssociationRole::create(['name' => 'Selected']);
		$parent->roles()->attach($existingRole);

		CrudModelAssociatorHelper::associateAdditionalRelation(
			$parent,
			'roles',
			[$selectedRole]
		);

		$this->assertSame([
			$existingRole->getKey(),
			$selectedRole->getKey(),
		], $parent->roles()->orderBy('association_roles.id')->pluck('association_roles.id')->all());
	}

	public function test_morph_to_many_relations_use_the_same_association_picker() : void
	{
		$this->createMorphAssociationTables();

		$parent = AssociationMorphParent::create(['name' => 'Parent']);
		$existingRole = AssociationRole::create(['name' => 'Already associated']);
		$selectedRole = AssociationRole::create(['name' => 'Selected']);
		$parent->allergens()->attach($existingRole);

		$manager = new class('show', $parent) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return [
					'show' => [
						'relations' => [
							'allergens' => [
								'hasAssociateButton' => true,
								'associateButtonText' => 'Associa allergeni',
							],
						],
					],
				];
			}
		};

		$relationship = $manager->getRelationships()->get('allergens');
		$table = new AssociationButtonsTable();
		$relationship->table = $table;
		$relationship->manageTableButtons();

		$this->assertSame('Associa allergeni', $table->buttons[0]->getText());
		$this->assertSame([$selectedRole->getKey()], $relationship->getAssociateableElements()->pluck('id')->all());

		CrudModelAssociatorHelper::associateAdditionalRelation($parent, 'allergens', [$selectedRole]);

		$this->assertSame([
			$existingRole->getKey(),
			$selectedRole->getKey(),
		], $parent->allergens()->orderBy('association_roles.id')->pluck('association_roles.id')->all());
	}

	public function test_association_endpoint_excludes_existing_models_and_refreshes_its_caller_table() : void
	{
		$this->createAssociationTables();

		$parent = AssociationParent::create(['name' => 'Parent']);
		$existingRole = AssociationRole::create(['name' => 'Already associated']);
		$selectedRole = AssociationRole::create(['name' => 'Selected']);
		$parent->roles()->attach($existingRole);

		$relationship = (new AssociationRelationshipsManager('show', $parent))
			->getRelationships()
			->get('roles');

		$this->assertSame([
			$selectedRole->getKey(),
		], $relationship->getAssociateableElements()->pluck('id')->all());

		$associateUrl = route('crud.relationships.associate', [
			'model' => $parent->getMorphClass(),
			'key' => $parent->getKey(),
			'relation' => 'roles',
		]);

		$this->get($associateUrl)
			->assertOk()
			->assertSee('Seleziona ruoli da associare')
			->assertSee('wannabedatatable')
			->assertSee('data-url=');

		$this->get($associateUrl . '?model=roles', [
			'X-Requested-With' => 'XMLHttpRequest',
		])
			->assertOk()
			->assertJsonStructure(['data']);

		$request = Request::create('/', 'POST', [
			'ids' => [$selectedRole->getKey()],
			'iframed' => true,
			'callertablename' => 'roles-table',
		]);

		$response = app(AssociateBelongsToManyController::class)->store(
			$request,
			$parent->getMorphClass(),
			(string) $parent->getKey(),
			'roles'
		);

		$this->assertSame('crud::utilities.messages.closeIframe', $response->name());
		$this->assertSame([
			$existingRole->getKey(),
			$selectedRole->getKey(),
		], $parent->roles()->orderBy('association_roles.id')->pluck('association_roles.id')->all());
	}

	private function createAssociationTables() : void
	{
		Schema::create('association_parents', function (Blueprint $table)
		{
			$table->id();
			$table->string('name');
		});

		Schema::create('association_roles', function (Blueprint $table)
		{
			$table->id();
			$table->string('name');
		});

		Schema::create('association_parent_role', function (Blueprint $table)
		{
			$table->unsignedBigInteger('parent_id');
			$table->unsignedBigInteger('role_id');
			$table->primary(['parent_id', 'role_id']);
		});
	}

	private function createMorphAssociationTables() : void
	{
		Schema::create('association_morph_parents', function (Blueprint $table)
		{
			$table->id();
			$table->string('name');
		});

		Schema::create('association_roles', function (Blueprint $table)
		{
			$table->id();
			$table->string('name');
		});

		Schema::create('association_allergenables', function (Blueprint $table)
		{
			$table->string('allergenable_type');
			$table->unsignedBigInteger('allergenable_id');
			$table->unsignedBigInteger('allergen_id');
			$table->primary(['allergenable_type', 'allergenable_id', 'allergen_id']);
		});
	}
}

class AssociationParent extends Model
{
	protected $table = 'association_parents';
	protected $guarded = [];
	public $timestamps = false;

	public function roles() : BelongsToMany
	{
		return $this->belongsToMany(AssociationRole::class, 'association_parent_role', 'parent_id', 'role_id');
	}

	public function getRelationshipsManagerClass() : ?string
	{
		return AssociationRelationshipsManager::class;
	}
}

class AssociationRole extends Model
{
	protected $table = 'association_roles';
	protected $guarded = [];
	public $timestamps = false;
}

class AssociationMorphParent extends Model
{
	protected $table = 'association_morph_parents';
	protected $guarded = [];
	public $timestamps = false;

	public function allergens() : MorphToMany
	{
		return $this->morphToMany(
			AssociationRole::class,
			'allergenable',
			'association_allergenables',
			'allergenable_id',
			'allergen_id'
		);
	}
}

class AssociationRelationshipsManager extends RelationshipsManager
{
	public function getAllRelationsParameters() : array
	{
		return [
			'show' => [
				'relations' => [
					'roles' => [
						'hasAssociateButton' => true,
						'fieldsGroups' => ['base' => ['fields' => []]],
						'translatedTitle' => 'ruoli',
					],
				],
			],
		];
	}
}

class AssociationButtonsTable
{
	public array $buttons = [];

	public function addButton(Button $button) : void
	{
		$this->buttons[] = $button;
	}
}
