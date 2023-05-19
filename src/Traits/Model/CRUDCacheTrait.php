<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait CRUDCacheTrait
{
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

    public function getOrFindCachedRelatedElement(
        string $relation,
        int $time = null
    ) : null|Model|Collection
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
        return implode("_", [
            class_basename($this),
            $this->id,
            Str::slug($key),
            $this->updated_at
        ]);
    }

    static function staticCacheKey(string $key, array $relations = [])
    {
        return implode("_", [
            class_basename(static::class),
            $key,
            json_encode($relations)
        ]);
    }

    static function findCached($id)
    {
        return cache()->remember(
            static::staticCacheKey($id),
            3600,
            function() use($id)
            {
                return static::find($id);
            }
        );
    }

    static function findCachedField(string $fieldname, $value)
    {
        return cache()->remember(
            static::staticCacheKey($fieldname . $value),
            3600,
            function() use($fieldname, $value)
            {
                return static::where($fieldname, $value)->first();
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
}