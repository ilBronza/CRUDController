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
