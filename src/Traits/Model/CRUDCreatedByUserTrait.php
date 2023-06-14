<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;

trait CRUDCreatedByUserTrait
{
    static $userForeignKey = 'user_id';

    public function getUserForeignKeyName()
    {
        return static::$userForeignKey;        
    }

    static function getLoggedUserId()
    {
        return Auth::id();
    }

    public static function bootCRUDCreatedByUserTrait()
    {
        static::saving(function ($model)
        {
            if(! $model->{$foreign = $model->getUserForeignKeyName()})
                $model->{$foreign} = static::getLoggedUserId();
        });

    }


}