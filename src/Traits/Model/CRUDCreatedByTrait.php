<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;

trait CRUDCreatedByTrait
{
    static $createdByTypeKeyName = 'created_by_type';
    static $createdByIdKeyName = 'created_by_id';

    public function createdBy()
    {
        return $this->morphTo('created_by');
    }

    public function checkIfUsesOnlyUserModel()
    {
        return static::$usesOnlyUserModel ?? false;
    }

    public function getCreatedByForeignKeyName()
    {
        return static::$createdByForeignKeyName;        
    }

    public function getCreadByTypeKeyName()
    {
        return static::$createdByTypeKeyName;
    }

    public function getCreadByIdKeyName()
    {
        return static::$createdByIdKeyName;
    }

    public function getCreatedByOperator()
    {
        return Auth::user();
    }


    static function setUserId($model) : void
    {
        $model->{$model->getCreadByForeignKey()} = Auth::id();
    }

    static function setMorphCreatedByModel($model) : void
    {
        if(! $creatingOperator = $model->getCreatedByOperator())
            return ;

        $model->{$model->getCreadByTypeKeyName()} = $creatingOperator->getMorphClass();
        $model->{$model->getCreadByIdKeyName()} = $creatingOperator->getKey();
    }

    public static function bootCRUDCreatedByTrait()
    {
        static::saving(function ($model)
        {
            //if already set exit
            if(($model->{$model->getCreadByTypeKeyName()})&&($model->{$model->getCreadByIdKeyName()}))
                return null;

            if($model->checkIfUsesOnlyUserModel())
                return static::setUserId($model);

            static::setMorphCreatedByModel($model);
        });

    }


}