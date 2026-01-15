<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Traits\PackagedClassesTrait;

trait PackagedHelpersTrait
{
	use PackagedClassesTrait;

	static function getHelperConfigPrefix() : string
	{
		return static::$classConfigPrefix;
	}

	public static function getClassname() : string
	{
		return config(
			static::getConfigParameterKey('helpers.' . static::getHelperConfigPrefix())
		);
	}
}