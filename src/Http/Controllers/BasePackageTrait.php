<?php

namespace IlBronza\CRUD\Http\Controllers;

trait BasePackageTrait
{
    public function calculateActionFromName()
    {
        $classname = class_basename($this);

        if(strpos($classname, "Show"))
            return 'show';

        if(strpos($classname, "Edit"))
            return 'edit';

        if(strpos($classname, "Update"))
            return 'edit';

        return 'create';
    }

    public function getAction()
    {
        return $this->action ?? $this->calculateActionFromName();
    }

    public function getBaseConfigName() : string
    {
        return static::$packageConfigPrefix;
    }

    public function getModelInstance(string $id) : Model
    {
        return $this->getModelClass()::findOrFail($id);
    }

    public function getPackageConfigName()
    {
        return static::$packageConfigPrefix;
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

    public function getGenericParametersFile() : ? string
    {
        return config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.parametersFiles.{$this->getAction()}");
    }
}