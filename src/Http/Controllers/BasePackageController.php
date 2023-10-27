<?php

namespace IlBronza\CRUD\Http\Controllers;

use IlBronza\CRUD\CRUD;
use Illuminate\Database\Eloquent\Model;

class BasePackageController extends CRUD
{
    public function getModelInstance(string $id) : Model
    {
        return $this->getModelClass()::findOrFail($id);
    }

    public function getPackageConfigName()
    {
        return static::$configFileName;
    }

    static function getModelConfigPrefix()
    {
        return static::$modelConfigPrefix;
    }

    public function getRouteBaseNamePrefix() : ? string
    {
        return config("{$this->getPackageConfigName()}.routePrefix");
    }

    public function setModelClass()
    {
        $this->modelClass = config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.class");
    }	
}