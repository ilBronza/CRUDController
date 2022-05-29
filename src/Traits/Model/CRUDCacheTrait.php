<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDCacheTrait
{
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
            Str::slug($key)
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

    static function findCached(int $id)
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