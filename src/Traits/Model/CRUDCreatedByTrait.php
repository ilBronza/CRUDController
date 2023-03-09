<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;

trait CRUDCreatedByTrait
{
    static $createdByTypeKeyName = 'created_by_type';
    static $createdByIdKeyName = 'created_by_id';

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

    public function getCreadByOperator()
    {
        return Auth::user();
    }

    public static function bootCRUDCreatedByTrait()
    {
        static::saving(function ($model)
        {
            if($model->checkIfUsesOnlyUserModel())
            {
                $model->{$model->getCreadByForeignKey()} = Auth::id();
            }
            else
            {
                if(! $creatingOperator = $model->getCreadByOperator())
                {
                    $model->{$model->getCreadByTypeKeyName()} = null;
                    $model->{$model->getCreadByIdKeyName()} = null;

                    return ;
                }
                else
                {
                    $model->{$model->getCreadByTypeKeyName()} = get_class($creatingOperator);
                    $model->{$model->getCreadByIdKeyName()} = $creatingOperator->getKey();
                }
            }
        });

    }


}