<?php

namespace IlBronza\CRUD\Traits\IlBronzaPackages;

use IlBronza\CRUD\Providers\RouterProvider\IbRouter;

use Illuminate\Contracts\Foundation\CachesConfiguration;

use function array_replace_recursive;
use function config;
use function dd;
use function get_class;

trait IlBronzaPackagesTrait
{
	abstract function manageMenuButtons();

	public function route(string $routeName, array $parameters = [])
	{
		return IbRouter::route($this, $routeName, $parameters);
	}

	static function getPackageConfigPrefix()
	{
		return static::$packageConfigPrefix;
	}

	public function getRoutePrefix() : ? string
	{
		return config(static::getPackageConfigPrefix() . ".routePrefix");
	}

	static function _getController(string $configKey) : string
	{
		if(! $result = config($configKey))
			dd("manca {$configKey} nella configurazione di " . static::class);

		return $result;
	}

	static function getController(string $target, string $type = null)
	{
		if(! $type)
			return static::_getController(static::getPackageConfigPrefix() . ".models.{$target}.controller");

		return static::_getController(
			static::getPackageConfigPrefix() . ".models.{$target}.controllers.{$type}"
		);
	}
}
