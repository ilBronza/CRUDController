<?php

namespace IlBronza\CRUD\Http\Controllers;

use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;

trait BasePackageShowTrait
{
    use CRUDShowTrait;
    use CRUDRelationshipTrait;

    public $allowedMethods = ['show'];

    public function getShowParametersFile() : ? string
    {
        return config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.parametersFiles.create");
    }

    public function getRelationshipsManagerClass()
    {
        return config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.relationshipsManagerClasses.show");
    }

    public function show(string $model)
    {
        $model = $this->findModel($model);

        return $this->_show($model);
    }
}