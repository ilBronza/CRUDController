<?php

namespace IlBronza\CRUD\Traits\Model;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function dd;
use function Exception;
use function get_class;

trait CRUDCacheTrait
{
	static function getCachableRelations()
	{
		return static::$cachableRelations ?? [];
	}

	static function staticCacheKey(string $key, array $relations = [])
	{
		return Str::slug(implode("_", [
			class_basename(static::class),
			$key,
			json_encode($relations)
		]));
	}

	static function findIfCached($id) : ?static
	{
		return cache()->get(
			static::staticCacheKey($id)
		);
	}

	static function findCached($id)
	{
		return cache()->remember(
			static::staticCacheKey($id), 3600, function () use ($id)
		{
			if ($cachableRelations = static::getCachableRelations())
				return static::with($cachableRelations)->find($id);

			return static::find($id);
		}
		);
	}

	static function findCachedAttribute($id, $attribute)
	{
		return cache()->remember(
			static::staticCacheKey($id . '_property' . $attribute), 3600, function () use ($id, $attribute)
		{
			return static::find($id)?->$attribute;
		}
		);
	}

	static function findCachedExtraField(string $fieldname, $value, array $with = [])
	{
		return cache()->remember(
			static::staticCacheKey($fieldname . $value . Str::slug(json_encode($with))), 3600, function () use ($fieldname, $value, $with)
		{
			$placeholder = static::make();

			if (! $cast = $placeholder->getCasts()[$fieldname])
				throw Exception('field ' . $fieldname . ' not found in model ' . get_class($placeholder) . ' neither in extrafields casts');

			//TODO occuparsi di sta roba
			if (! $cast == 'IlBronza\CRUD\Models\Casts\ExtraField')
				dd('occuparsi di sta roba');

			$query = static::whereHas('extraFields', function ($_query) use ($fieldname, $value)
			{
				$_query->where($fieldname, $value);
			});

			if ($with)
				$query->with($with);

			return $query->first();
		}
		);
	}

	static function findCachedField(string $fieldname, $value, array $with = [])
	{
		//TODO DEPRECATED
		return static::findCachedByField($fieldname, $value, $with);
	}

	static function findCachedByName($name, array $with = [])
	{
		return static::findCachedByField('name', $name, $with);
	}

	static function findCachedByField(string $fieldname, $value, array $with = [])
	{
		return cache()->remember(
			static::staticCacheKey($fieldname . $value . Str::slug(json_encode($with))), 3600, function () use ($fieldname, $value, $with)
		{
			try
			{
				$query = static::where($fieldname, $value);

				if ($with)
					$query->with($with);

				return $query->first();
			}
			catch (Exception $e)
			{
				if (! $e->getCode() == "42S22")
					throw $e;

				return static::findCachedExtraField($fieldname, $value, $with);
			}
		}
		);
	}

	static function staticCacheMethod(string $method)
	{
		return cache()->remember(
			static::staticCacheKey($method), 3600, function () use ($method)
		{
			return static::$method();
		}
		);
	}

	public function getOrFindCachedRelation(string $relation, int $time = null) : null|Model|Collection
	{
		if ($this->relationLoaded($relation))
			return $this->$relation;

		return cache()->remember(
			$this->cacheKey($relation), $time ?? config("mes.cache." . get_class($this) . "." . $relation, config("mes.cache.modelGeneral", 3600)), function () use ($relation)
		{
			return $this->$relation;
		}
		);
	}

	public function getOrFindCachedRelatedElements(string $relation, int $time = null) : Collection
	{
		return $this->getOrFindCachedRelation($relation, $time);
	}

	public function getOrFindCachedRelatedElement(string $relation, int $time = null) : ?Model
	{
		if ($this->relationLoaded($relation))
			return $this->$relation;

		$relatedClass = $this->$relation()->getRelated();
		$foreignKeyName = $this->$relation()->getForeignKeyName();
		$foreignKey = $this->$foreignKeyName;

		return $relatedClass::findCached($foreignKey);
	}

	/**
	 * return class unique id based key for cache
	 *
	 * @param  string  $key
	 *
	 * @return string
	 */
	public function cacheKey(string $key)
	{
		return Str::slug(implode("_", [
			class_basename($this),
			$this->getKey(),
			$key,
			$this->updated_at
		]));
	}

	public function cacheMethod(string $method)
	{
		return cache()->remember(
			$this->cacheKey($method), 3600, function () use ($method)
		{
			return $this->$method();
		}
		);
	}

	public function getCachedCalculatedProperty(string $name, callable $callable = null)
	{
		return cache()->remember(
			$this->cacheKey($name), 3600, function () use ($name, $callable)
		{
			if (! $callable)
				return $this->{$name};

			return $callable();
		}
		);
	}
}