<?php

namespace IlBronza\CRUD\Http\Controllers\Traits;

use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;

trait PackageStandardIndexTrait
{
    public $allowedMethods = ['index'];

    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;

    function getIndexElementsRelationsArray() : array
    {
        return [];
    }

    function getIndexElementsScopesArray() : array
    {
        return $this->scopes;
    }

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
        $query = $this->getModelClass()::query();

        if($with = $this->getIndexElementsRelationsArray())
            $query->with($with);

        foreach($scopes = $this->getIndexElementsScopesArray() as $scope)
            $query->{$scope}();

        return $query->get();
    }
}