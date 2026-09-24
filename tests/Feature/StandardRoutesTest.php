<?php

namespace IlBronza\CRUD\Tests\Feature;

use IlBronza\CRUD\Tests\Fixtures\Controllers\GadgetCreateStoreController;
use IlBronza\CRUD\Tests\Fixtures\Controllers\GadgetDestroyController;
use IlBronza\CRUD\Tests\Fixtures\Controllers\GadgetEditUpdateController;
use IlBronza\CRUD\Tests\Fixtures\Controllers\GadgetIndexController;
use IlBronza\CRUD\Tests\Fixtures\Controllers\GadgetShowController;
use IlBronza\CRUD\Tests\Fixtures\Models\Gadget;
use IlBronza\CRUD\Tests\TestCase;
use Illuminate\Support\Facades\Route;

// Smoke test delle 7 route dichiarate in CRUDResourceController::$allowedMethods.
// Non verifica i contenuti: verifica che il giro completo
// routing -> controller -> form/fieldset -> datatable -> buttons non esploda.
// Il database e' in memoria e riparte da zero a ogni test: niente RefreshDatabase,
// che sotto php-wasm fa crashare il runtime.
class StandardRoutesTest extends TestCase
{
	protected function defineRoutes($router)
	{
		$router->group(['middleware' => 'web'], function ($router)
		{
			$router->get('gadgets', [GadgetIndexController::class, 'index'])->name('gadgets.index');
			$router->post('gadgets/create-empty', [GadgetIndexController::class, 'createEmpty'])->name('gadgets.createEmpty');
			$router->get('gadgets/create', [GadgetCreateStoreController::class, 'create'])->name('gadgets.create');
			$router->post('gadgets', [GadgetCreateStoreController::class, 'store'])->name('gadgets.store');
			$router->get('gadgets/{gadget}', [GadgetShowController::class, 'show'])->name('gadgets.show');
			$router->get('gadgets/{gadget}/edit', [GadgetEditUpdateController::class, 'edit'])->name('gadgets.edit');
			$router->put('gadgets/{gadget}', [GadgetEditUpdateController::class, 'update'])->name('gadgets.update');
			$router->delete('gadgets/{gadget}', [GadgetDestroyController::class, 'destroy'])->name('gadgets.destroy');

			$router->resource('resource-gadgets', GadgetIndexController::class)->only('createEmpty');
			$router->childResource(['parents', 'gadgets'], GadgetIndexController::class);
		});
	}

	protected function setUp() : void
	{
		parent::setUp();

		$this->actingAsSuperadmin();
	}

	private function createGadget() : Gadget
	{
		return Gadget::create([
			'name' => 'Cacciavite',
			'description' => 'A stella'
		]);
	}

	public function test_index_risponde()
	{
		$this->createGadget();

		$this->get('gadgets')
			->assertOk()
			->assertSee('Crea vuoto');
	}

	public function test_create_empty_crea_un_modello_senza_passare_dal_form()
	{
		$response = $this->postJson('gadgets/create-empty');

		$response->assertOk()->assertJson([
			'success' => true,
		]);

		$this->assertDatabaseCount('gadgets', 1);
		$this->assertDatabaseHas('gadgets', ['name' => null]);
	}

	public function test_resource_and_child_resource_register_the_create_empty_post_route()
	{
		foreach ([
			'resource-gadgets.createEmpty' => 'resource-gadgets/create-empty',
			'parents.gadgets.createEmpty' => 'parents/{parent}/gadgets/create-empty',
		] as $name => $uri)
		{
			$route = Route::getRoutes()->getByName($name);

			$this->assertNotNull($route);
			$this->assertSame($uri, $route->uri());
			$this->assertSame(['POST'], $route->methods());
		}
	}

	// Le 4 route che costruiscono un form passano da CRUDDbFieldsTrait::getDbFields(),
	// che lancia "describe <tabella>": sintassi solo MySQL. Su sqlite non partono.
	// Vanno riattivate quando getDbFields() diventa indipendente dal driver.
	private function skipSeServeDescribe()
	{
		$this->markTestSkipped('getDbFields() usa "describe": funziona solo su MySQL');
	}

	public function test_create_risponde()
	{
		$this->skipSeServeDescribe();

		$this->get('gadgets/create')->assertOk();
	}

	public function test_store_salva_il_modello()
	{
		$this->skipSeServeDescribe();

		$response = $this->post('gadgets', [
			'name' => 'Martello',
			'description' => 'Da carpentiere'
		]);

		$response->assertRedirect();

		$this->assertDatabaseHas('gadgets', ['name' => 'Martello']);
	}

	public function test_show_risponde()
	{
		$gadget = $this->createGadget();

		$this->get('gadgets/' . $gadget->getKey())->assertOk();
	}

	public function test_edit_risponde()
	{
		$this->skipSeServeDescribe();

		$gadget = $this->createGadget();

		$this->get('gadgets/' . $gadget->getKey() . '/edit')->assertOk();
	}

	public function test_update_modifica_il_modello()
	{
		$this->skipSeServeDescribe();

		$gadget = $this->createGadget();

		$response = $this->put('gadgets/' . $gadget->getKey(), [
			'name' => 'Cacciavite a taglio',
			'description' => 'A stella'
		]);

		$response->assertRedirect();

		$this->assertDatabaseHas('gadgets', ['name' => 'Cacciavite a taglio']);
	}

	public function test_destroy_elimina_il_modello()
	{
		$gadget = $this->createGadget();

		$response = $this->delete('gadgets/' . $gadget->getKey());

		$response->assertRedirect();

		$this->assertSoftDeleted('gadgets', ['id' => $gadget->getKey()]);
	}
}
