<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDCacheAutomaticSetterTrait
{
    abstract static function getAutomaticCachingRelationships() : array;

    public function storeInCache()
    {
        cache()->put(
            static::staticCacheKey($this->getKey()),
            $this,
            3600
        );
    }

    public function automaticallyStoreInCache($model)
    {
        foreach(static::getAutomaticCachingRelationships() as $relationship)
        {
            if($model->relationLoaded($relationship))
                continue;

            $model->load($relationship);
        }

        $model->storeInCache();
    }

    protected static function bootCRUDCacheAutomaticSetterTrait()
    {
        static::saved(function($model)
        {
            $model->automaticallyStoreInCache($model);
        });
    }
}