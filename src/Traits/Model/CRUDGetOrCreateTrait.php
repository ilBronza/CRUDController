<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDGetOrCreateTrait
{
    static function getOrCreateByName(string $name) : static
    {
        if($byname = static::getProjectClassname()::getByName($name))
            return $byname;

        $model = static::getProjectClassname()::make();
        $model->name = $name;

        $model->save();

        return $model;
    }    
}