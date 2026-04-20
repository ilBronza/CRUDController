<?php

namespace IlBronza\CRUD\Helpers;

use function config;

class CompulsoryConfigHelper
{
	public static function get(string $configKey) : mixed
	{
		if($result = config($configKey))
			return $result;

		throw new \Exception('Manca la configurazione per ' . $configKey);
	}

	public static function getForPackagedModel(string $packageConfigPrefix, string $modelConfigPrefix, string $key) : mixed
	{
		$fullKey = implode(".", [
			$packageConfigPrefix,
			'models',
			$modelConfigPrefix,
			$key,
		]);

		return static::get($fullKey);
	}
}
