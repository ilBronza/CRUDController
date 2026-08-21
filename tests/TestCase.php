<?php

namespace IlBronza\CRUD\Tests;

use IlBronza\AccountManager\AccountManagerServiceProvider;
use IlBronza\Buttons\ButtonsServiceProvider;
use IlBronza\CRUD\CRUDServiceProvider;
use IlBronza\Datatables\DatatablesServiceProvider;
use IlBronza\Form\FormServiceProvider;
use IlBronza\FormField\FormFieldServiceProvider;
use IlBronza\Menu\MenuServiceProvider;
use IlBronza\UikitTemplate\UikitTemplate;
use IlBronza\UikitTemplate\UikitTemplateServiceProvider;
use IlBronza\AccountManager\Models\User;
use IlBronza\Ukn\UknServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
	protected function getPackageProviders($app)
	{
		return [
			PermissionServiceProvider::class,
			AccountManagerServiceProvider::class,
			UknServiceProvider::class,
			MenuServiceProvider::class,
			UikitTemplateServiceProvider::class,
			ButtonsServiceProvider::class,
			FormFieldServiceProvider::class,
			FormServiceProvider::class,
			DatatablesServiceProvider::class,
			CRUDServiceProvider::class
		];
	}

	protected function defineEnvironment($app)
	{
		$app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
		$app['config']->set('app.locale', 'it');
		$app['config']->set('app.template', UikitTemplate::class);
		$app['config']->set('menu.childrenPerColumn', 8);
		$app['config']->set('menu.serviceProviders', []);

		$app['config']->set('database.default', 'testing');
		$app['config']->set('database.connections.testing', [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => ''
		]);

		$app['config']->set('auth.defaults.guard', 'web');
		$app['config']->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
		$app['config']->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);

		$app['config']->set('media-library.media_model', \Spatie\MediaLibrary\MediaCollections\Models\Media::class);

		// CRUDModelTrait usa LogsActivity: senza questa config il resolver esplode
		$app['config']->set('activitylog.default_auth_driver', null);
		$app['config']->set('activitylog.enabled', false);
	}

	protected function defineDatabaseMigrations()
	{
		$this->loadMigrationsFrom(__DIR__ . '/Fixtures/migrations');
		$this->loadMigrationsFrom(__DIR__ . '/../../datatables/database/migrations');
	}

	// I middleware di CRUD chiamano hasRole() sull'utente autenticato:
	// senza superadmin le route protette rispondono 403.
	protected function actingAsSuperadmin() : User
	{
		$user = User::create([
			'name' => 'Tester',
			'email' => 'tester@example.com',
			'password' => 'secret'
		]);

		$user->assignRole(Role::create(['name' => 'superadmin', 'guard_name' => 'web']));

		$this->actingAs($user);

		return $user;
	}
}
