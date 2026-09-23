# CRUD

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

Before install media library 
(https://github.com/spatie/laravel-medialibrary)

Before install spatie permissions 
(https://github.com/spatie/laravel-medialibrary)

Before install activity log 
(https://spatie.be/docs/laravel-activitylog/v4/introduction)
USE php artisan vendor:publish to publish activity-log migrations and config. Installation is bugged

Via Composer

``` bash
$ composer require ilbronza/crud
```

``` bash
$ php artisan notifications:table
$ php artisan migrate

$ php artisan vendor:publish --tag=crud-migrations
$ php artisan migrate
```

on config/media-library.php

``` bash
    'media_model' => IlBronza\CRUD\Models\Media::class,
    'path_generator' => IlBronza\CRUD\Helpers\MediaPathGenerator::class,
```

on app.js
and then run npm

``` bash
	require('./ilBronza.crud.js');
```



## Usage

## Broadcast delle modifiche CRUD

Il broadcast è opzionale e pubblica sul canale privato configurato un evento
`crud.model.changed` con questo payload:

```json
{
    "model": "App\\Models\\Order",
    "key": 42,
    "text": "Order updated",
    "action": "updated",
    "origin": {
        "browserTabId": "5a91610f-29f5-48ec-bb66-bdf46b3a12a3",
        "tableId": "orders"
    }
}
```

`origin` è opzionale. Quando una modifica proviene da una DataTable, il
browser invia `X-IB-Datatable-Browser-Tab-Id` e
`X-IB-Datatable-Table-Id`; l'evento li cattura alla costruzione, prima di
un'eventuale coda, e li ripubblica come metadati di correlazione. Non usare
questi valori per autorizzazione o autenticazione.

Per `action: "created"`, il client usa `key` (la chiave primaria effettiva,
non necessariamente un intero) per decidere se recuperare la riga e
aggiungerla alla propria tabella. Questo evita di definire nel package un
formato di riga comune a CRUD e DataTables.

Il canale per `App\Models\Order` è
`channel.crud-events.models.order`. Abilitare `CRUD_BROADCAST_ENABLED=true` e
autorizzare i canali privati in `routes/channels.php` dell'applicazione:

```php
Broadcast::channel('channel.crud-events.models.{modelClass}', fn ($user) => $user !== null);
```

Con Laravel Echo il listener è:

```js
Echo.private('channel.crud-events.models.order')
    .listen('.crud.model.changed', (event) => {
        console.log(event.model, event.key, event.text);
    });
```

Il testo libero si personalizza nel controller CRUD; restituire `null` evita
l'invio per quella specifica azione. Le creazioni sono intercettate dai model
base, quindi vengono inviate anche quando avvengono fuori da un controller CRUD:

```php
class Order extends BaseModel {}
```

```php
public function getCrudBroadcastText(Model $model, string $action): ?string
{
    return "Ordine {$model->getKey()} {$action}";
}
```

Il prefisso è configurabile con `CRUD_BROADCAST_CHANNEL`; per usare una
convenzione diversa, sovrascrivere `getCrudBroadcastChannel()`.
Gli invii standard del controller sono effettuati dopo update (anche inline
editor), delete, force-delete e archive. I model che estendono `BaseModel` o
`BasePivotModel` emettono `created` indipendentemente dal controller che li ha
generati. Anche `touch()` conserva il comportamento Eloquent e, se aggiorna il
timestamp, emette un evento `updated` sul canale del model. Per disabilitarlo
su un model specifico, sovrascrivere `getCrudTouchedBroadcastText()` e
restituire `null`.

Ogni canale effettivamente usato viene registrato una sola volta in
`crud__channels` (`name`, timestamp). Per creare la tabella, pubblicare e
applicare le migration del package:

```bash
php artisan vendor:publish --tag=crud-migrations
php artisan migrate
```

`BaseModel` e `BasePivotModel` includono anche `BroadcastCrudModelEventsTrait`:
gli eventi Eloquent successivi a una scrittura — `created`, `updated`, `saved`,
`trashed`, `deleted`, `restored` e `forceDeleted` — generano automaticamente una
notifica con la stessa action, anche per salvataggi e cancellazioni eseguiti da
servizi, observer o job. Il trait usa `broadcastCrudAction()`, quindi rispetta la
configurazione, i testi, i canali del model e l'invio dopo il commit già previsti
da `BroadcastCrudTrait`.

Questa prima versione è aggiuntiva: gli invii del CRUD e quelli già presenti
per `created` e `touch()` rimangono attivi. Non c'è accorpamento o deduplicazione:
un salvataggio che modifica un model produce `updated` e `saved`, mentre una
creazione produce `created` e `saved`; il CRUD o `touch()` possono aggiungere
ulteriori notifiche. Anche `saveQuietly()`, `updateQuietly()`,
`deleteQuietly()` generano la notifica realtime dopo aver scritto nel database,
pur continuando a silenziare observer e gli altri eventi Eloquent. Le query
massive, `forceDeleteQuietly()`, `restoreQuietly()` e `updateWithoutEvent()` non
attivano i nuovi listener: devono invocare esplicitamente
`broadcastCrudAction('updated')` dopo l'update. Le modifiche alle pivot sono
intercettate quando Eloquent emette gli eventi dell'istanza del pivot; una query
sulla tabella di relazione non garantisce questi eventi.

Le azioni custom possono inviare direttamente un testo libero:

```php
$this->broadcastCrudMessage($order, 'Il pagamento è stato confermato', 'paymentConfirmed');
```

## Batch field read (ib-editor-read-batch)

Oltre al read singolo (`ib-editor-read` + `field`), la route di update accetta una lettura cumulativa di più campi in una sola richiesta. Viene usata dal coordinatore batch di FormField (vedi readme di `ilbronza/formfield`).

Richiesta (`PUT` via `_method` sulla stessa route di update):

```
ib-editor-read-batch: true
fields[]: total_cost
fields[]: total_revenue
```

Risposta:

``` json
{
    "success": true,
    "fetch-fields-batch": true,
    "values": { "total_cost": 123.45, "total_revenue": 456.78 },
    "model-id": "..."
}
```

Il flusso è gestito da:

- `CrudRequestHelper::isEditorBatchReadRequest()` — riconosce il flag
- `CRUDUpdateEditorBatchReadTrait::returnFieldsFromEditor()` — legge ogni campo da `$model->{$field}` e ritorna la mappa `values`

Il trait è incluso automaticamente da `CRUDUpdateTrait`; il read singolo `returnFieldFromEditor` resta invariato.

## Relazioni

creare un getter con i possibili valori di relazione in un select
``` bash
	public function getPossiblePaymenttypesValuesArray() : array
	{
		return Paymenttype::getProjectClassName()::all()->pluck('name', 'id')->toArray();
	}
```

## RelationshipsManager

Gestisce il display delle relazioni in una pagina di dettaglio, tipicamente Show o Edit

Supporta anche sezioni fetcher autonome: dichiarare `getSectionsParameters()`
con voci `type => 'relationship'` o `type => 'fetcher'`. Le sezioni condividono
i layout `Flat`, `Switcher` e `Toggler` e conservano l'ordine dell'array.
I manager che dichiarano `getRelationsParameters()` restano compatibili.
Vedi [la guida alle sezioni e ai fetcher](docs/come-usare-crud.md#relationshipsmanagers--relazioni-nella-show).

### Dichiarazione

``` bash
<?php

class QuotationRelationManager Extends RelationshipsManager
{
	public function getRelationsParameters() : array
	{
		return [
			# dichiarazione complessa, dove vengono specificati parametri aggiuntivi oltre al controller
			    'quotationrows' => [
                                        'controller' => config('products.models.quotationrow.controllers.index'),
                                        'elementGetterMethod' => 'getQuotationrowsForShowRelation',

                                        #alternativo a fieldsGroups
                                        'fieldsGroupsParametersFile' => config('products.models.quotationrow.fieldsGroupsFiles.byQuotation'),

                                        #alternativo a fieldsGroupsParametersFile
                                        'fieldsGroups' => [
                                            'base' => [
                                                'translationPrefix' => 'operators::fields',
                                                'fields' =>
                                                    [
                                                        'mySelfPrimary' => 'primary',
                                                        'mySelfEdit' => 'links.edit',
                                                        'contracttype.name' => 'flat',
                            
                                                        'internal_approval_rating' => 'flat',
                                                        'level' => 'flat',
                            
                                                        'cost_company_day' => 'flat',
                                                        'cost_gross_day' => 'flat',
                                                        'cost_neat_day' => 'flat',
                            
                                                        'mySelfDelete' => 'links.delete'
                                                    ]
                                            ]
                                        ]

                                    ],

				# dichiarazione semplice in cui si specifica solo il controller di Show
					'project' => config('products.models.project.controllers.show'),

				# dichiarazione semplice in cui si specifica solo il controller di Index, che si occupa
				# di impostare i fieldsgroup tramite il suo metodo "getRelatedFieldsArray"
					'dossiers' => config('filecabinet.models.dossier.controllers.index'),
		];
	}
}```


## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [author name][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/ilbronza/crud.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/ilbronza/crud.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/ilbronza/crud/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/ilbronza/crud
[link-downloads]: https://packagist.org/packages/ilbronza/crud
[link-travis]: https://travis-ci.org/ilbronza/crud
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/ilbronza
[link-contributors]: ../../contributors
