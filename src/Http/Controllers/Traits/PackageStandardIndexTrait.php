<?php

namespace IlBronza\CRUD\Http\Controllers\Traits;

use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;

trait PackageStandardIndexTrait
{
    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;

    abstract function getIndexElementsRelationsArray() : array;

    public function getIndexFieldsArray()
    {
        return config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.fieldsGroupsFiles.index")::getFieldsGroup();
    }

    public function getRelatedFieldsArray()
    {
        return config("{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.fieldsGroupsFiles.related")::getFieldsGroup();
    }

    public function getIndexElements()
    {
        return $this->getModelClass()::all();
    }


}