# Come usare CRUD

Guida all'uso del pacchetto `ilbronza/crud` per costruire la gestione di un'entità in un pacchetto IlBronza. L'esempio di riferimento è il package **Vehicles**, che segue lo spezzettamento dei controller come previsto da CRUD.

## Filosofia

Un'entità non ha un controller unico: ha **un controller per azione** (o coppia di azioni affini). Ogni controller dichiara solo i metodi che gli competono in `$allowedMethods`, e CRUD blocca via middleware tutto il resto. La configurazione (model, tabella, controller, file dei campi) vive nel config del pacchetto, e le route vengono risolte leggendo il config: niente riferimenti diretti alle classi nelle route.

I vantaggi: ogni controller è minuscolo, le azioni si abilitano/sovrascrivono dal config senza toccare il pacchetto, e l'app può sostituire un singolo controller estendendolo.

## Struttura

Per un'entità `Vehicle` in un pacchetto `vehicles`:

```
src/Http/Controllers/
	CRUDVehiclesPackageController.php        ← base di pacchetto
	Vehicles/
		VehicleCRUD.php                      ← base di entità
		VehicleIndexController.php
		VehicleCreateStoreController.php
		VehicleEditUpdateController.php
		VehicleShowController.php
		VehicleDestroyController.php
	Providers/
		FieldsGroups/
			VehicleFieldsGroupParametersFile.php     ← colonne datatable (index)
		Fieldsets/
			VehicleCreateStoreFieldsetsParameters.php ← campi form
			VehicleEditUpdateFieldsetsParameters.php
			VehicleShowFieldsetsParameters.php
		RelationshipsManagers/
			VehicleRelationManager.php               ← relazioni mostrate nella show
```

## 1. Il controller base di pacchetto

Uno per pacchetto. Estende `BasePackageController` di CRUD e dichiara il prefisso del config. Qui vanno anche i metodi condivisi da tutti i controller del pacchetto (es. bottoni cross-entità).

```php
use IlBronza\CRUD\Http\Controllers\BasePackageController;

class CRUDVehiclesPackageController extends BasePackageController
{
	static $packageConfigPrefix = 'vehicles';

	public function getRouteBaseNamePrefix() : ? string
	{
		return config('vehicles.routePrefix');
	}

	public function setModelClass()
	{
		$this->modelClass = config("vehicles.models.{$this->configModelClassName}.class");
	}
}
```

`BasePackageController` fornisce già `setModelClass()` e `getRouteBaseNamePrefix()` basati su `$packageConfigPrefix`; sovrascriverli serve solo per comportamenti particolari.

## 2. La classe CRUD di entità

Una per entità. Collega i controller al config tramite `$configModelClassName`, la chiave sotto `models` nel config. Tutti i controller dell'entità la estendono.

```php
class VehicleCRUD extends CRUDVehiclesPackageController
{
	public ?bool $updateEditor = false;
	public $configModelClassName = 'vehicle';
}
```

## 3. I controller spezzettati

Ogni controller usa il trait CRUD dell'azione e dichiara `$allowedMethods`. Un metodo non dichiarato viene bloccato dal middleware `CRUDAllowedMethods`.

### Index

```php
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;

class VehicleIndexController extends VehicleCRUD
{
	use CRUDPlainIndexTrait;
	use CRUDIndexTrait;

	public $allowedMethods = ['index'];

	public function getIndexFieldsArray()
	{
		return config('vehicles.models.vehicle.fieldsGroupsFiles.index')::getTracedFieldsGroup();
	}

	public function getRelatedFieldsArray()
	{
		return $this->getIndexFieldsArray();
	}

	public function getIndexElements()
	{
		return $this->getModelClass()::with('vehicleType')
				->withLastKmReading()
				->get();
	}
}
```

`CRUDPlainIndexTrait` fornisce il metodo pubblico `index()`, `CRUDIndexTrait` la logica. `getIndexElements()` è il punto dove definire query, eager loading e scope. `getRelatedFieldsArray()` serve quando l'entità compare come tabella relazionata nella show di un altro modello. Con `addIndexButtons()` si aggiungono bottoni sopra la tabella.

### Create + Store

Create e store vivono nello stesso controller perché condividono i campi del form.

```php
use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;

class VehicleCreateStoreController extends VehicleCRUD
{
	use CRUDCreateStoreTrait;
	use CRUDRelationshipTrait;

	public $allowedMethods = ['create', 'store'];

	public function getGenericParametersFile() : ? string
	{
		return config('vehicles.models.vehicle.parametersFiles.create');
	}
}
```

Il parameters file (vedi §4) definisce campi e regole di validazione: il form si monta e valida da solo.

### Edit + Update

```php
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use Illuminate\Http\Request;

class VehicleEditUpdateController extends VehicleCRUD
{
	use CRUDEditUpdateTrait;

	public $allowedMethods = ['edit', 'update'];

	public function getEditParametersFile() : ? string
	{
		return config('vehicles.models.vehicle.parametersFiles.edit');
	}

	public function edit(string $vehicle)
	{
		$vehicle = $this->findModel($vehicle);

		return $this->_edit($vehicle);
	}

	public function update(Request $request, $vehicle)
	{
		$vehicle = $this->findModel($vehicle);

		return $this->_update($request, $vehicle);
	}
}
```

Il pattern è sempre lo stesso: il metodo pubblico risolve il modello con `findModel()` e delega al metodo `_action()` del trait.

### Show

```php
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;

class VehicleShowController extends VehicleCRUD
{
	use CRUDShowTrait;
	use CRUDRelationshipTrait;

	public $allowedMethods = ['show'];

	public function setShowButtons()
	{
		$this->showButtons[] = $this->getModel()->getCreateKmreadingButton();
	}

	public function show(string $vehicle)
	{
		$vehicle = $this->findModel($vehicle);

		return $this->_show($vehicle);
	}
}
```

`CRUDRelationshipTrait` fa leggere il relationships manager dal config (`relationshipsManagerClasses.show`), che decide quali relazioni mostrare come tabelle nella pagina show.

### Destroy

```php
use IlBronza\CRUD\Traits\CRUDDeleteTrait;

class VehicleDestroyController extends VehicleCRUD
{
	use CRUDDeleteTrait;

	public $allowedMethods = ['destroy'];

	public function destroy($vehicle)
	{
		$vehicle = $this->findModel($vehicle);

		return $this->_destroy($vehicle);
	}
}
```

## 4. I providers

### FieldsGroups — colonne della datatable

Estendono `FieldsGroupParametersFile` (package Datatables) e dichiarano le colonne dell'index in `getFieldsGroup()`:

```php
use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class VehicleFieldsGroupParametersFile extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
			'translationPrefix' => 'vehicles::fields',
			'fields' =>
			[
				'mySelfPrimary' => 'primary',
				'mySelfEdit' => 'links.edit',
				'mySelfSee' => 'links.see',

				'plate' => 'flat',

				'vehicleType' => [
					'type' => 'links.seeName',
					'width' => '10em',
				],
				'vehicleModel.brand' => 'flat',

				'mySelfDelete' => 'links.delete'
			]
		];
	}
}
```

I campi `mySelf*` generano le colonne di azione (link edit, show, delete). La notazione col punto attraversa le relazioni.

### Fieldsets — campi del form

Estendono `FieldsetParametersFile` (package Form) e dichiarano fieldset, campi e regole in `_getFieldsetsParameters()`:

```php
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class VehicleShowFieldsetsParameters extends FieldsetParametersFile
{
	public function _getFieldsetsParameters() : array
	{
		return [
			'baseData' => [
				'translationPrefix' => 'vehicles::fields',
				'fields' => [
					'name' => ['text' => 'string|required'],
					'plate' => ['text' => 'string|required'],
					'type_id' => [
						'type' => 'select',
						'multiple' => false,
						'rules' => 'string|required|exists:vehicles__types,id',
						'relation' => 'vehicleType'
					],
				],
				'width' => ['large']
			],
			'costs' => [
				'translationPrefix' => 'vehicles::fields',
				'fields' => [
					'cost_per_km' => [
						'type' => 'number',
						'rules' => 'numeric|nullable|min:0',
						'vertical' => true
					],
				],
				'width' => ['small']
			],
		];
	}
}
```

Quando create ed edit condividono i campi, il file create estende quello show/edit e resta vuoto:

```php
class VehicleCreateStoreFieldsetsParameters extends VehicleShowFieldsetsParameters
{
}
```

### RelationshipsManagers — relazioni nella show

Il manager può comporre anche sezioni fetcher autonome, senza una relazione
Eloquent. Per mescolare relazioni e fetcher nello stesso `Flat`, `Switcher` o
`Toggler`, usare `getSectionsParameters()`:

```php
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;

class VehicleRelationManager extends RelationshipsManager
{
	public function getSectionsParameters() : array
	{
		return [
			'kmreadings' => [
				'type' => 'relationship',
				'controller' => config('vehicles.models.kmreading.controllers.index'),
			],
			'notes' => [
				'type' => 'fetcher',
				'title' => 'Note',
				'url' => route('vehicles.notes', ['vehicle' => $this->getModel()]),
				'refresh' => true,
				'canExpand' => true,
			],
		];
	}
}
```

Le sezioni seguono l'ordine dell'array; è valido anche un manager con soli
fetcher. La route `vehicles.notes` dell'esempio deve essere definita
dall'applicazione e restituire il contenuto HTML del pannello. Il browser la
richiede attraverso il fetcher di UikitTemplate, con `ibFetcher=true`; il manager
non carica quel contenuto lato server. Il caricamento iniziale e i comandi di
refresh/espansione usano il comportamento del fetcher esistente.

Per un fetcher, `url` è obbligatorio e deve essere una stringa non vuota. `title`
è già tradotto e, se omesso, usa il nome della sezione. Gli altri parametri sono
inoltrati a `IlBronza\UikitTemplate\Fetcher`, per esempio `refresh`, `canExpand`,
`buttons` e `card`. Di default il fetcher non aggiunge una propria card, perché
il layout del manager fornisce già il contenitore.

I manager esistenti con `getRelationsParameters()` o con il formato legacy di
`getAllRelationsParameters()` continuano a funzionare. Quando si adotta
`getSectionsParameters()`, questo diventa la mappa completa: `type` omesso e
la forma breve con il solo controller identificano una relazione. Se si
estende un manager esistente, si può partire da
`parent::getSectionsParameters()` e aggiungere le nuove sezioni.

`getSections()` espone tutti i pannelli; `getRelationships()` e le API per la
singola relazione espongono soltanto relazioni. Associazioni, editor e refresh
delle righe continuano quindi a usare le relazioni. Il precedente `fetcherUrl`
di una relazione mantiene il suo significato: aggiunge un fetcher sotto la sua
tabella, senza creare una scheda autonoma.

Le sezioni implementano `ManagerSection` (`getName()`, `getCardTitle()`,
`getToggleId()`, `prepare()`, `render()`). Il controller le prepara prima di
renderizzare il layout. Per un tipo aggiuntivo si può estendere
`createSection()` e restituire un'implementazione di questo contratto,
delegando gli altri tipi a `parent::createSection()`.

Per usare un canale di refresh dedicato per una singola DataTable, dichiarare
`refreshTableBroadcastChannel` nei parametri della relazione:

```php
'orders' => [
    'refreshTableBroadcastChannel' => 'channel.tables.orders.refresh',
    'controller' => config('products.models.order.controllers.index'),
],
```

Se omesso, Datatables mantiene il proprio canale convenzionale, derivato dal
nome della tabella.

```php
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;

class VehicleRelationManager extends RelationshipsManager
{
	public function getRelationsParameters() : array
	{
		$relations = [];

		$relations['kmreadings'] = config('vehicles.models.kmreading.controllers.index');

		return $relations;
	}
}
```

Ogni relazione punta al controller index dell'entità relazionata: la tabella nella show viene resa con le colonne di `getRelatedFieldsArray()` di quel controller.

Per mostrare il pulsante di creazione della relazione e personalizzarne il testo,
usare `hasCreateButton` e opzionalmente `createButtonText`. Il testo fornito a
`createButtonText` viene usato direttamente, quindi deve essere già tradotto:

```php
'orders' => [
    'hasCreateButton' => true,
    'createButtonText' => 'Aggiungi ordine',
    'controller' => config('products.models.order.controllers.index'),
],
```

Per aprire il form di creazione in un iframe/modale e ricaricare la sola tabella
della relazione dopo il salvataggio, aggiungere `createButtonIframe`:

```php
'orders' => [
    'hasCreateButton' => true,
    'createButtonIframe' => true,
    'controller' => config('products.models.order.controllers.index'),
],
```

Il default globale e' `crud.realtionshipManagers.createButtonIframe` (false).
Il valore dichiarato sulla relazione ha sempre la precedenza: `false` mantiene
il redirect anche se il default globale e' attivo.

Per una relazione `belongsToMany` o `morphToMany`, il pulsante di creazione non
e' applicabile: gli elementi esistono gia' e va creata la sola riga pivot. Per
mostrare il pulsante **Associa**, che apre una modale con DataTable
selezionabile, usare `hasAssociateButton`:

```php
'roles' => [
    'hasAssociateButton' => true,
    'associateButtonText' => 'Associa ruoli',
    'controller' => config('accounts.models.role.controllers.index'),
],
```

Il salvataggio e' additivo: associa gli elementi selezionati senza rimuovere
quelli gia' presenti. Il picker esclude per default gli elementi gia' associati;
per applicare un filtro o un'autorizzazione ulteriore, `associateElementsGetter`
puo' essere una callback che riceve il model padre e la relazione Eloquent e
restituisce la collection selezionabile.

Questo flusso e' adatto a pivot senza attributi obbligatori. Se la riga pivot
richiede dati aggiuntivi (per esempio quantita', ruolo o date), dichiarare un
`buttonsMethods` personalizzato che apra il form specifico del pivot.

L'URL del picker contiene il morph alias del model padre (per esempio `Product`),
non il nome della classe. Il controller accetta soltanto alias registrati nella
morph map dell'applicazione.

## 5. Il config

Tutto si aggancia sotto `models.<chiave>`, dove la chiave è il `$configModelClassName`:

```php
'vehicle' => [
	'class' => Vehicle::class,
	'table' => 'vehicles__vehicles',
	'fieldsGroupsFiles' => [
		'index' => VehicleFieldsGroupParametersFile::class
	],
	'parametersFiles' => [
		'create' => VehicleCreateStoreFieldsetsParameters::class,
		'edit' => VehicleEditUpdateFieldsetsParameters::class,
		'show' => VehicleShowFieldsetsParameters::class
	],
	'relationshipsManagerClasses' => [
		'show' => VehicleRelationManager::class,
	],
	'controllers' => [
		'index' => VehicleIndexController::class,
		'create' => VehicleCreateStoreController::class,
		'store' => VehicleCreateStoreController::class,
		'show' => VehicleShowController::class,
		'edit' => VehicleEditUpdateController::class,
		'update' => VehicleEditUpdateController::class,
		'destroy' => VehicleDestroyController::class,
	]
],
```

Un'app che usa il pacchetto può sostituire un controller, un parameters file o il model semplicemente sovrascrivendo la chiave nel config pubblicato.

## 6. Le route

Le route non citano mai le classi: risolvono il controller dal config tramite l'helper del pacchetto (`getController(entità, azione)`).

```php
Route::group(['prefix' => 'vehicles'], function()
{
	Route::get('', [Vehicles::getController('vehicle', 'index'), 'index'])->name('vehicles.index');
	Route::get('create', [Vehicles::getController('vehicle', 'create'), 'create'])->name('vehicles.create');
	Route::post('', [Vehicles::getController('vehicle', 'store'), 'store'])->name('vehicles.store');

	Route::get('{vehicle}', [Vehicles::getController('vehicle', 'show'), 'show'])->name('vehicles.show');
	Route::get('{vehicle}/edit', [Vehicles::getController('vehicle', 'edit'), 'edit'])->name('vehicles.edit');
	Route::put('{vehicle}', [Vehicles::getController('vehicle', 'edit'), 'update'])->name('vehicles.update');

	Route::delete('{vehicle}/delete', [Vehicles::getController('vehicle', 'destroy'), 'destroy'])->name('vehicles.destroy');
});
```

I nomi route usano il `routePrefix` del pacchetto (es. `ibVehicles.vehicles.index`).

## 7. Varianti utili

**Create da un modello padre** — un metodo custom va dichiarato in `$allowedMethods` e può preimpostare valori con `getModelDefaultParameters()`:

```php
class KmreadingCreateStoreController extends KmreadingCRUD
{
	use CRUDCreateStoreTrait;
	use CRUDRelationshipTrait;

	public $returnBack = true;

	public $allowedMethods = ['createFromVehicle', 'create', 'store'];

	public function getModelDefaultParameters() : array
	{
		$result = [
			'registered_at' => Carbon::now()
		];

		if($this->vehicle ?? false)
			$result['vehicle_id'] = $this->vehicle->getKey();

		return $result;
	}

	public function createFromVehicle(Vehicle $vehicle)
	{
		$this->vehicle = $vehicle;

		return $this->create();
	}
}
```

**Proprietà comode sul controller** — `public $returnBack = true` torna alla pagina di provenienza dopo il salvataggio; `public ?bool $updateEditor = false` disattiva l'editing inline; `CRUDCreateStoreByParentModelTrait` gestisce il caso padre/figlio in modo strutturato.

## Checklist per una nuova entità

Creare il model con `CRUDModelTrait`; aggiungere la chiave nel config con `class`, `table`, `fieldsGroupsFiles`, `parametersFiles`, `controllers`; creare la classe `<Entità>CRUD` con `$configModelClassName`; creare i cinque controller (Index, CreateStore, EditUpdate, Show, Destroy); creare il FieldsGroup per l'index e i Fieldsets per i form; se serve la show con relazioni, creare il RelationshipsManager; dichiarare le route con `getController()`.
