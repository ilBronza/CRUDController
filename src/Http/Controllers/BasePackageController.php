<?php

namespace IlBronza\CRUD\Http\Controllers;

use IlBronza\CRUD\CRUD;
use Illuminate\Database\Eloquent\Model;

class BasePackageController extends CRUD
{
    public function getBaseConfigName() : string
    {
        return static::$packageConfigPrefix;
        // return static::$configFileName;
    }

    public function getModelInstance(string $id) : Model
    {
        return $this->getModelClass()::findOrFail($id);
    }

    public function getPackageConfigName()
    {
        return static::$packageConfigPrefix;
        // return static::$configFileName;
    }

    public function getModelConfigPrefix()
    {
		if(isset(static::$modelConfigPrefix))
            return static::$modelConfigPrefix;

		return $this->configModelClassName;
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