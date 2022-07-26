<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;

trait CRUDCreatedByTrait
{
    static $createdByTypeKeyName = 'created_by_type';
    static $createdByIdKeyName = 'created_by_id';

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
            if(! $creatingOperator = $model->getCreadByOperator())
            {
                $model->{$model->getCreadByTypeKeyName()} = null;
                $model->{$model->getCreadByIdKeyName()} = null;

                return ;
            }

            $model->{$model->getCreadByTypeKeyName()} = get_class($creatingOperator);
            $model->{$model->getCreadByIdKeyName()} = $creatingOperator->getKey();
        });

    }


}