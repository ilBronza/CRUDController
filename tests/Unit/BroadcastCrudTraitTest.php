<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Events\CrudModelBroadcasted;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\CRUDModelBroadcastingTrait;
use IlBronza\CRUD\Traits\Model\BroadcastCrudModelEventsTrait;
use IlBronza\CRUD\Traits\Model\BroadcastCrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class BroadcastCrudTraitTest extends TestCase
{
	protected function defineDatabaseMigrations()
	{
		parent::defineDatabaseMigrations();

		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
	}

	public function test_created_models_are_broadcast_automatically() : void
	{
		config()->set('crud.broadcast.enabled', true);
		Event::fake([CrudModelBroadcasted::class]);

		$model = BroadcastCrudTestModel::create(['name' => 'Created model']);

		$this->assertDatabaseHas('crud__channels', [
			'name' => $model->getCrudBroadcastChannel(),
		]);

		Event::assertDispatched(
			CrudModelBroadcasted::class,
			fn(CrudModelBroadcasted $event) => $event->action === 'created'
				&& $event->key === $model->getKey()
		);
	}

	public function test_touch_broadcasts_an_updated_action() : void
	{
		$this->freezeTime();
		$model = BroadcastCrudTestModel::create(['name' => 'Touched model']);

		config()->set('crud.broadcast.enabled', true);
		Event::fake([CrudModelBroadcasted::class]);
		$this->travel(1)->seconds();

		$this->assertTrue($model->touch());

		Event::assertDispatched(
			CrudModelBroadcasted::class,
			fn(CrudModelBroadcasted $event) => $event->action === 'updated'
				&& $event->key === $model->getKey()
		);
	}

	public function test_null_legacy_text_hook_suppresses_created_broadcast() : void
	{
		config()->set('crud.broadcast.enabled', true);
		Event::fake([CrudModelBroadcasted::class]);

		BroadcastCrudSilentTestModel::create(['name' => 'Silent model']);

		Event::assertNotDispatched(CrudModelBroadcasted::class);
		$this->assertDatabaseCount('crud__channels', 0);
	}

	public function test_controller_channels_are_added_to_model_channels() : void
	{
		$model = BroadcastCrudTestModel::create(['name' => 'Updated model']);
		$controller = new class
		{
			use CRUDModelBroadcastingTrait;

			public function getCrudBroadcastChannel(Model $model, string $action) : string | array | null
			{
				return ['context.channel', '', 'context.channel'];
			}
		};

		config()->set('crud.broadcast.enabled', true);
		Event::fake([CrudModelBroadcasted::class]);

		$controller->broadcastCrudMessage($model, 'Restored model', 'restored');

		$this->assertDatabaseHas('crud__channels', [
			'name' => $model->getCrudBroadcastChannel(),
		]);
		$this->assertDatabaseHas('crud__channels', [
			'name' => 'context.channel',
		]);
		$this->assertDatabaseCount('crud__channels', 2);

		Event::assertDispatched(
			CrudModelBroadcasted::class,
			fn(CrudModelBroadcasted $event) => $event->action === 'restored'
				&& count($event->broadcastOn()) === 2
		);
	}
}

class BroadcastCrudTestModel extends Model
{
	use BroadcastCrudTrait;
	use BroadcastCrudModelEventsTrait;

	protected $table = 'gadgets';
	protected $fillable = ['name'];

	public function getName() : ?string
	{
		return $this->name;
	}

	public function getTranslatedClassName() : string
	{
		return 'test model';
	}
}

class BroadcastCrudSilentTestModel extends BroadcastCrudTestModel
{
	public function getCrudCreatedBroadcastText() : ?string
	{
		return null;
	}
}
