<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipParameters;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\CRUDEditTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipsManagerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

class RelationshipsManagerContractTest extends TestCase
{
	public function test_new_manager_exposes_a_direct_relation_map() : void
	{
		$manager = new DirectContractRelationshipsManager(new RelationshipsManagerContractParent());

		$this->assertSame([
			'children' => [
				'controller' => 'children.index',
			],
		], $manager->getRelationsParameters());
		$this->assertSame(['children'], $manager->getRelationshipsNamesArray());
		$this->assertNull($manager->legacyRelationsType);
	}

	public function test_legacy_show_container_is_normalized_to_a_direct_relation_map() : void
	{
		$manager = new LegacyShowContractRelationshipsManager(
			'show',
			new RelationshipsManagerContractParent()
		);

		$this->assertSame([
			'children' => [
				'controller' => 'legacy.show.children.index',
			],
		], $manager->getRelationsParameters());
		$this->assertSame(['children'], $manager->getRelationshipsNamesArray());
	}

	public function test_legacy_edit_container_is_normalized_to_a_direct_relation_map() : void
	{
		$manager = new LegacyEditContractRelationshipsManager(
			'edit',
			new RelationshipsManagerContractParent()
		);

		$this->assertSame([
			'children' => [
				'controller' => 'legacy.edit.children.index',
			],
		], $manager->getRelationsParameters());
	}

	#[DataProvider('legacyContainerFallbackCases')]
	public function test_legacy_container_selection_prefers_show_then_edit(array $parameters, ?string $legacyType, array $expected) : void
	{
		$manager = new class(new RelationshipsManagerContractParent()) extends RelationshipsManager
		{
			public array $parameters = [];
			public int $parametersReads = 0;

			public function getAllRelationsParameters() : array
			{
				$this->parametersReads++;

				return $this->parameters;
			}
		};

		$manager->parameters = $parameters;

		if ($legacyType !== null)
			$manager->setLegacyRelationsType($legacyType);

		$this->assertSame($expected, $manager->getRelationsParameters());
		$this->assertSame(1, $manager->parametersReads);
	}

	public static function legacyContainerFallbackCases() : array
	{
		$show = ['children' => 'legacy.show.children.index'];
		$edit = ['children' => 'legacy.edit.children.index'];

		return [
			'show without action context' => [['show' => ['relations' => $show]], null, $show],
			'edit without action context' => [['edit' => ['relations' => $edit]], null, $edit],
			'show wins over edit even for an edit action' => [
				['show' => ['relations' => $show], 'edit' => ['relations' => $edit]], 'edit', $show,
			],
			'edit fallback for a show action' => [['edit' => ['relations' => $edit]], 'show', $edit],
			'empty show wins over nonempty edit' => [
				['show' => ['relations' => []], 'edit' => ['relations' => $edit]], null, [],
			],
			'empty edit is valid' => [['edit' => ['relations' => []]], null, []],
		];
	}

	public function test_legacy_manager_reports_missing_show_and_edit_containers_explicitly() : void
	{
		$manager = new class(new RelationshipsManagerContractParent()) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return ['create' => ['relations' => []]];
			}
		};

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('must declare a "show" or "edit" container');

		$manager->getRelationsParameters();
	}

	public function test_legacy_manager_reports_a_missing_relations_container_explicitly() : void
	{
		$manager = new class('show', new RelationshipsManagerContractParent()) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return [
					'show' => [],
				];
			}
		};

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('must declare ["show" => ["relations" => [...]]].');

		$manager->getRelationsParameters();
	}

	public function test_edit_controller_uses_its_selected_manager_instead_of_the_model_default() : void
	{
		$controller = new RelationshipsManagerContractEditController();
		$controller->modelInstance = new RelationshipsManagerContractParent();

		$controller->shareExtraParameters();

		$this->assertInstanceOf(ControllerSelectedRelationshipsManager::class, $controller->relationshipManager);
		$this->assertNotInstanceOf(ModelDefaultRelationshipsManager::class, $controller->relationshipManager);
		$this->assertNull($controller->relationshipManager->legacyRelationsType);
	}

	public function test_refresh_and_ajax_use_the_manager_selected_by_the_controller() : void
	{
		$refreshController = new RelationshipsManagerContractRefreshController();
		$refreshController->modelInstance = new RelationshipsManagerContractParent();

		$this->assertSame('controller-selected-refresh', $refreshController->manageRelatedRefreshRow());
		$this->assertInstanceOf(RefreshAjaxSelectedRelationshipsManager::class, $refreshController->relationshipManager);

		$this->app->instance('request', Request::create(
			'/relationships?model=children',
			'GET',
			[],
			[],
			[],
			['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
		));

		$ajaxController = new RelationshipsManagerContractRefreshController();
		$ajaxController->modelInstance = new RelationshipsManagerContractParent();

		$this->assertSame('controller-selected-ajax', $ajaxController->useRelationshipsManager());
		$this->assertInstanceOf(RefreshAjaxSelectedRelationshipsManager::class, $ajaxController->relationshipManager);
	}

	public function test_single_relation_uses_the_manager_selected_by_the_controller() : void
	{
		$controller = new RelationshipsManagerContractSingleRelationController();
		$controller->modelInstance = new RelationshipsManagerContractParent();

		$this->assertSame(
			SingleRelationSelectedRelationshipsManager::class,
			$controller->useSingleRelationRelationshipsManager('children', [123])
		);
		$this->assertInstanceOf(SingleRelationSelectedRelationshipsManager::class, $controller->relationshipManager);
	}
}

class RelationshipsManagerContractParent extends Model
{
	public function children() : HasMany
	{
		return $this->hasMany(RelationshipsManagerContractChild::class, 'parent_id');
	}

	public function getRelationshipsManagerClass() : ?string
	{
		return ModelDefaultRelationshipsManager::class;
	}
}

class RelationshipsManagerContractChild extends Model
{
}

class DirectContractRelationshipsManager extends RelationshipsManager
{
	public function getRelationsParameters() : array
	{
		return [
			'children' => [
				'controller' => 'children.index',
			],
		];
	}
}

class LegacyShowContractRelationshipsManager extends RelationshipsManager
{
	public function getAllRelationsParameters() : array
	{
		return [
			'show' => [
				'relations' => [
					'children' => [
						'controller' => 'legacy.show.children.index',
					],
				],
			],
		];
	}
}

class LegacyEditContractRelationshipsManager extends RelationshipsManager
{
	public function getAllRelationsParameters() : array
	{
		return [
			'edit' => [
				'relations' => [
					'children' => [
						'controller' => 'legacy.edit.children.index',
					],
				],
			],
		];
	}
}

class ModelDefaultRelationshipsManager extends RelationshipsManager
{
	public function getRelationsParameters() : array
	{
		return [];
	}
}

class ControllerSelectedRelationshipsManager extends RelationshipsManager
{
	public function getRelationsParameters() : array
	{
		return [];
	}
}

class RefreshAjaxSelectedRelationshipsManager extends RelationshipsManager
{
	public function getRelationsParameters() : array
	{
		return [];
	}

	public function manageRefreshRow()
	{
		return 'controller-selected-refresh';
	}

	public function manageAjaxTableRequest()
	{
		return 'controller-selected-ajax';
	}
}

class SingleRelationSelectedRelationshipsManager extends RelationshipsManager
{
	public function getRelationsParameters() : array
	{
		return [
			'children' => [],
		];
	}

	public function createRelationship(string $name, $parameters) : RelationshipParameters
	{
		return new SingleRelationContractRelationshipParameters($name, $parameters, $this);
	}
}

class SingleRelationContractRelationshipParameters extends RelationshipParameters
{
	public function renderTableRowsArray()
	{
		return get_class($this->getRelationshipsManager());
	}
}

class RelationshipsManagerContractEditController extends CRUD
{
	use CRUDEditTrait;

	public $modelClass = RelationshipsManagerContractParent::class;
	public $allowedMethods = ['edit'];

	public function getRelationshipsManagerClass() : ?string
	{
		return ControllerSelectedRelationshipsManager::class;
	}
}

class RelationshipsManagerContractRefreshController extends CRUD
{
	use CRUDRelationshipsManagerTrait;

	public $modelClass = RelationshipsManagerContractParent::class;
	public $allowedMethods = ['edit'];

	public function getRelationshipsManagerClass() : ?string
	{
		return RefreshAjaxSelectedRelationshipsManager::class;
	}

	public function getRelationshipsManagerLegacyType() : string
	{
		return 'edit';
	}
}

class RelationshipsManagerContractSingleRelationController extends CRUD
{
	use CRUDRelationshipsManagerTrait;

	public $modelClass = RelationshipsManagerContractParent::class;
	public $allowedMethods = ['show'];

	public function getRelationshipsManagerClass() : ?string
	{
		return SingleRelationSelectedRelationshipsManager::class;
	}
}
