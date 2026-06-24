<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Traits\PackagedClassesTrait;

trait PackagedHelpersTrait
{
	use PackagedClassesTrait;

	static function getHelperConfigPrefix() : string
	{
		if(isset(static::$classConfigPrefix))
			return static::$classConfigPrefix;

		return lcfirst(
			class_basename(static::class)
		);
	}

	public static function getClassname() : string
	{
		return cconfig(
			static::getConfigParameterKey('helpers.' . static::getHelperConfigPrefix())
		);
	}
}