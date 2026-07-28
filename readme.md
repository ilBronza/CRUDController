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
    "action": "updated"
}
```

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
l'invio per quella specifica azione. Per inviare una creazione anche quando il
model viene creato fuori dal controller CRUD, aggiungere il trait al model:

```php
use IlBronza\CRUD\Traits\Model\BroadcastCreateTrait;

class Order extends BaseModel
{
    use BroadcastCreateTrait;
}
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
editor), delete, force-delete e archive. Il trait emette `created` per ogni
creazione del model, indipendentemente dal controller che l'ha generata.

Ogni canale effettivamente usato viene registrato una sola volta in
`crud__channels` (`name`, timestamp). Per creare la tabella, pubblicare e
applicare le migration del package:

```bash
php artisan vendor:publish --tag=crud-migrations
php artisan migrate
```

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

### Dichiarazione

``` bash
<?php

class QuotationRelationManager Extends RelationshipsManager
{
	public  function getAllRelationsParameters() : array
	{
		return [
			'show' => [
				'relations' => [
				
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
				]
			]
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
