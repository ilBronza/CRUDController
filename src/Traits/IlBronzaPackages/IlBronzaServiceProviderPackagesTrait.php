<?php

namespace IlBronza\CRUD\Traits\IlBronzaPackages;

use Illuminate\Contracts\Foundation\CachesConfiguration;

trait IlBronzaServiceProviderPackagesTrait
{
	protected function mergeConfigFrom($path, $key)
	{
		if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
			$config = $this->app->make('config');

			$config->set($key, array_replace_recursive(
				require $path, $config->get($key, [])
			));
		}
	}


}
