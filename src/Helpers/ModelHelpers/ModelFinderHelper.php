<?php

namespace IlBronza\CRUD\Helpers\ModelHelpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ModelFinderHelper
{
	public static function getByClassKey(string $modelClass, string $key) : ? Model
	{
		$classname = Relation::getMorphedModel($modelClass);

		return $classname::getProjectClassname()::find($key);
	}

	public static function getByClassSlug(string $modelClass, string $slug) : ? Model
	{
		$classname = Relation::getMorphedModel($modelClass);

		return $classname::getProjectClassname()::where('slug', $slug)->first();
	}
}