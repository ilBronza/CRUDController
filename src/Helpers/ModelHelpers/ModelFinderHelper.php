<?php

namespace IlBronza\CRUD\Helpers\ModelHelpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

use function is_null;

class ModelFinderHelper
{
	static function getFullQualifiedClassByClassName(string $modelClass) : string
	{
		return Relation::getMorphedModel($modelClass);
	}

	public static function getByClassKey(string $modelClass, string $key) : ?Model
	{
		$classname = Relation::getMorphedModel($modelClass);

		if(is_null($classname))
			$classname = $modelClass;

		if(method_exists($classname::make(), 'gpc'))
			return $classname::gpc()::find($key);

		return $classname::find($key);
	}

	public static function getByClassSlug(string $modelClass, string $slug) : ?Model
	{
		$classname = Relation::getMorphedModel($modelClass);

		return $classname::getProjectClassName()::where('slug', $slug)->first();
	}
}