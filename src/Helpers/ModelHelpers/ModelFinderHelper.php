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

	/**
	 * risolve l'alias morph e restituisce la classe di progetto
	 */
	static function getProjectClassByClassName(string $modelClass) : string
	{
		$classname = Relation::getMorphedModel($modelClass);

		if(is_null($classname))
			$classname = $modelClass;

		if(method_exists($classname::make(), 'gpc'))
			return $classname::gpc();

		return $classname;
	}

	public static function getByClassKey(string $modelClass, string $key) : ?Model
	{
		return static::getProjectClassByClassName($modelClass)::find($key);
	}

	public static function getArchivedByClassKey(string $modelClass, string $key) : ?Model
	{
		return static::getProjectClassByClassName($modelClass)::archived()->find($key);
	}

	public static function getByClassSlug(string $modelClass, string $slug) : ?Model
	{
		$classname = Relation::getMorphedModel($modelClass);

		return $classname::getProjectClassName()::where('slug', $slug)->first();
	}
}
