<?php

namespace IlBronza\CRUD\Helpers\ModelHelpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

use function is_null;

class ModelFinderHelper
{
	public static function getByClassKey(string $modelClass, string $key) : ?Model
	{
		$classname = Relation::getMorphedModel($modelClass);

		if(is_null($classname))
			$classname = $modelClass;

		return $classname::gpc()::find($key);
	}

	public static function getByClassSlug(string $modelClass, string $slug) : ?Model
	{
		$classname = Relation::getMorphedModel($modelClass);

		return $classname::getProjectClassName()::where('slug', $slug)->first();
	}
}