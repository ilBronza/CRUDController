<?php

namespace IlBronza\CRUD\Http\Controllers;

use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;

use function config;

trait BasePackageShowTrait
{
    use CRUDShowTrait;
    use CRUDRelationshipTrait;

    public $allowedMethods = ['show'];

    public function getShowParametersFile() : ? string
    {
		if($result = config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.parametersFiles.show"))
			return $result;

        return config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.parametersFiles.create");
    }

    public function getRelationshipsManagerClass()
    {
        return config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.relationshipsManagerClasses.show");
    }

}