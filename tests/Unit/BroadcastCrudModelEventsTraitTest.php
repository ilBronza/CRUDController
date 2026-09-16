<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Events\CrudModelBroadcasted;
use IlBronza\CRUD\Models\BaseModel;
use IlBronza\CRUD\Models\BasePivotModel;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\CRUDModelBroadcastingTrait;
use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;

class BroadcastCrudModelEventsTraitTest extends TestCase
{
	protected function defineDatabaseMigrations()
	{
		parent::defineDatabaseMigrations();

		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
	}

	protected function setUp() : void
	{
		parent::setUp();

		config()->set('crud.broadcast.enabled', false);
		$this->freezeTime();
	}

	public static function modelClasses() : array
	{
		return [
			'base model' => [AutomaticBroadcastTestModel::class],
			'base pivot model' => [AutomaticBroadcastTestPivot::class],
		];
	}

	private function startRecording() : void
	{
		config()->set('crud.broadcast.enabled', true);
		Event::fake([CrudModelBroadcasted::class]);
	}

	private function broadcastActions() : array
	{
		return Event::dispatched(CrudModelBroadcasted::class)
			->map(fn($arguments) => $arguments[0]->action)
			->all();
	}

	#[DataProvider('modelClasses')]
	public function test_direct_saves_broadcast_each_change_and_keep_controller_notifications(string $modelClass) : void
	{
		$model = $modelClass::create(['name' => 'Original']);
		$this->startRecording();

		foreach(['First', 'Second', 'Third'] as $name)
		{
			$model->name = $name;
			$this->assertTrue($model->save());
		}

		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 6);
		$this->assertSame(
			['updated', 'saved', 'updated', 'saved', 'updated', 'saved'],
			$this->broadcastActions()
		);
		Event::assertDispatched(CrudModelBroadcasted::class, fn(CrudModelBroadcasted $event) =>
			$event->action === 'updated'
			&& $event->model === $modelClass
			&& $event->key === $model->getKey()
			&& $event->broadcastOn()[0]->name === 'private-' . $model->getCrudBroadcastChannel()
		);

		$controller = new class {
			use CRUDModelBroadcastingTrait;
		};
		$controller->broadcastCrudModel($model, 'updated');

		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 7);
	}

	#[DataProvider('modelClasses')]
	public function test_existing_creation_and_touch_notifications_remain_active(string $modelClass) : void
	{
		$this->startRecording();
		$model = $modelClass::create(['name' => 'Created']);

		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 2);
		$this->assertSame(['created', 'saved'], $this->broadcastActions());

		Event::fake([CrudModelBroadcasted::class]);
		$this->travel(1)->seconds();
		$this->assertTrue($model->touch());

		// Il nuovo listener si aggiunge all'invio esplicito già presente in touch().
		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 3);
		$this->assertSame(['updated', 'saved', 'updated'], $this->broadcastActions());
	}

	#[DataProvider('modelClasses')]
	public function test_deletion_restore_and_force_deletion_work_without_a_controller(string $modelClass) : void
	{
		$model = $modelClass::create(['name' => 'Deleted']);
		$this->startRecording();

		$model->delete();
		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 2);
		$this->assertSame(['trashed', 'deleted'], $this->broadcastActions());
		$this->assertSoftDeleted('gadgets', ['id' => $model->getKey()]);

		Event::fake([CrudModelBroadcasted::class]);
		$model->restore();
		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 3);
		$this->assertSame(['updated', 'saved', 'restored'], $this->broadcastActions());

		Event::fake([CrudModelBroadcasted::class]);
		$model->forceDelete();
		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 2);
		$this->assertSame(['deleted', 'forceDeleted'], $this->broadcastActions());
		$this->assertDatabaseMissing('gadgets', ['id' => $model->getKey()]);
	}

	#[DataProvider('modelClasses')]
	public function test_saved_event_is_emitted_for_unchanged_saves_and_mass_updates_are_not_observed(string $modelClass) : void
	{
		$model = $modelClass::create(['name' => 'Original']);
		$this->startRecording();

		$model->save();
		$modelClass::whereKey($model->getKey())->update(['name' => 'Mass update']);

		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 1);
		$this->assertSame(['saved'], $this->broadcastActions());
		$this->assertDatabaseHas('gadgets', ['id' => $model->getKey(), 'name' => 'Mass update']);
	}

	#[DataProvider('modelClasses')]
	public function test_quiet_instance_writes_are_broadcast(string $modelClass) : void
	{
		$model = $modelClass::create(['name' => 'Original']);
		$this->startRecording();

		$model->updateQuietly(['name' => 'Quiet update']);
		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 1);
		$this->assertSame(['updated'], $this->broadcastActions());

		Event::fake([CrudModelBroadcasted::class]);
		$model->deleteQuietly();
		Event::assertDispatchedTimes(CrudModelBroadcasted::class, 1);
		Event::assertDispatched(CrudModelBroadcasted::class, fn($event) => $event->action === 'deleted');

	}

	#[DataProvider('modelClasses')]
	public function test_disabled_broadcasts_remain_disabled(string $modelClass) : void
	{
		Event::fake([CrudModelBroadcasted::class]);
		$model = $modelClass::create(['name' => 'Original']);
		$model->update(['name' => 'Updated']);
		$model->delete();

		Event::assertNotDispatched(CrudModelBroadcasted::class);
	}

	public function test_model_can_suppress_automatic_update_notifications() : void
	{
		$model = SilentAutomaticBroadcastTestModel::create(['name' => 'Original']);
		$this->startRecording();
		$model->update(['name' => 'Silent update']);

		Event::assertNotDispatched(CrudModelBroadcasted::class);
	}

	#[DataProvider('modelClasses')]
	public function test_broadcasts_wait_for_commit_and_are_discarded_on_rollback(string $modelClass) : void
	{
		$model = $modelClass::create(['name' => 'Original']);
		$broadcaster = new class extends NullBroadcaster {
			public array $messages = [];

			public function broadcast(array $channels, $event, array $payload = [])
			{
				$this->messages[] = $payload;
			}
		};

		config()->set('queue.default', 'sync');
		config()->set('broadcasting.default', 'crud-test');
		config()->set('broadcasting.connections.crud-test', ['driver' => 'crud-test']);
		Broadcast::extend('crud-test', fn() => $broadcaster);
		config()->set('crud.broadcast.enabled', true);

		DB::beginTransaction();
		try
		{
			$model->update(['name' => 'Committed']);
			$this->assertSame([], $broadcaster->messages);
			DB::commit();
		}
		finally
		{
			if(DB::transactionLevel())
				DB::rollBack();
		}

		$this->assertCount(2, $broadcaster->messages);
		$this->assertSame(['updated', 'saved'], array_column($broadcaster->messages, 'action'));
		$this->assertSame($model->getKey(), $broadcaster->messages[0]['key']);

		DB::beginTransaction();
		try
		{
			$model->update(['name' => 'Rolled back']);
			$this->assertCount(2, $broadcaster->messages);
		}
		finally
		{
			DB::rollBack();
		}

		$this->assertCount(2, $broadcaster->messages);
		$this->assertSame('Committed', $model->fresh()->name);
	}
}

class AutomaticBroadcastTestModel extends BaseModel
{
	protected $table = 'gadgets';
	protected $fillable = ['name'];
}

class AutomaticBroadcastTestPivot extends BasePivotModel
{
	protected $table = 'gadgets';
	protected $fillable = ['name'];
	public $incrementing = true;
	public $timestamps = true;
}

class SilentAutomaticBroadcastTestModel extends AutomaticBroadcastTestModel
{
	public function getCrudBroadcastTextForUpdated() : ?string
	{
		return null;
	}

	public function getCrudBroadcastTextForSaved() : ?string
	{
		return null;
	}
}
