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
