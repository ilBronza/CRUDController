<?php

namespace IlBronza\CRUD\Traits\Model;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait CRUDCacheTrait
{
    static function getCachableRelations()
    {
        return static::$cachableRelations ?? [];
    }

    public function getOrFindCachedRelation(
        string $relation,
        int $time = null
    ) : null|Model|Collection
    {
        if($this->relationLoaded($relation))
            return $this->$relation;

        return cache()->remember(
            $this->cacheKey($relation),
            $time ?? config("mes.cache." . get_class($this) . "." . $relation, config("mes.cache.modelGeneral", 3600)),
            function() use($relation)
            {
                return $this->$relation;
            }
        );
    }

    public function getOrFindCachedRelatedElements(
        string $relation,
        int $time = null
    ) : Collection
    {
        return $this->getOrFindCachedRelation($relation, $time);
    }

    public function getOrFindCachedRelatedElement(
        string $relation,
        int $time = null
    ) : ? Model
    {
        if($this->relationLoaded($relation))
            return $this->$relation;

        $relatedClass = $this->$relation()->getRelated();
        $foreignKeyName = $this->$relation()->getForeignKeyName();
        $foreignKey = $this->$foreignKeyName;

        return $relatedClass::findCached($foreignKey);
    }

    /**
     * return class unique id based key for cache
     *
     * @param string $key 
     *
     * @return string
     */
    public function cacheKey(string $key)
    {
        return Str::slug(implode("_", [
            class_basename($this),
            $this->id,
            $key,
            $this->updated_at
        ]));
    }

    static function staticCacheKey(string $key, array $relations = [])
    {
        return Str::slug(implode("_", [
            class_basename(static::class),
            $key,
            json_encode($relations)
        ]));
    }

    static function findIfCached($id) : ? static
    {
        return cache()->get(
            static::staticCacheKey($id)
        );
    }

    static function findCached($id)
    {
        return cache()->remember(
            static::staticCacheKey($id),
            3600,
            function() use($id)
            {
                if($cachableRelations = static::getCachableRelations())
                    return static::with($cachableRelations)->find($id);

                return static::find($id);
            }
        );
    }

    static function findCachedAttribute($id, $attribute)
    {
        return cache()->remember(
            static::staticCacheKey($id . '_property' . $attribute),
            3600,
            function() use($id, $attribute)
            {
                return static::find($id)?->$attribute;
            }
        );
    }

    static function findCachedField(string $fieldname, $value, array $with = [])
    {
        return cache()->remember(
            static::staticCacheKey($fieldname . $value . Str::slug(json_encode($with))),
            3600,
            function() use($fieldname, $value, $with)
            {
                $query = static::where($fieldname, $value);

                if($with)
                    $query->with($with);

                return $query->first();
            }
        );
    }

    public function cacheMethod(string $method)
    {
        return cache()->remember(
            $this->cacheKey($method),
            3600,
            function() use($method)
            {
                return $this->$method();
            });
    }

    static function staticCacheMethod(string $method)
    {
        return cache()->remember(
            static::staticCacheKey($method),
            3600,
            function() use($method)
            {
                return static::$method();
            });
    }

    public function getCachedCalculatedProperty(string $name, callable $callable = null)
    {
        return cache()->remember(
            $this->cacheKey($name),
            3600,
            function() use($name, $callable)
            {
                if(! $callable)
                    return $this->{$name};

                return $callable();
            });        
    }
}