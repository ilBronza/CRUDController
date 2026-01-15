<?php

namespace IlBronza\CRUD\Http\Controllers\Traits\StandardTraits;

use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;

use function config;

trait PackageStandardIndexTrait
{
    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;

	public $allowedMethods = ['index'];
	protected string $indexFieldsArraySuffix = 'index';

    function getIndexElementsRelationsArray() : array
    {
        return [];
    }

    function getIndexElementsScopesArray() : array
    {
    	if(isset($this->scopes))
    		return $this->scopes;

        return [];
    }

	public function getIndexFieldsArraySuffix() : string
	{
		return $this->indexFieldsArraySuffix;
	}

	public function getIndexFieldsArray() : array
	{
		return $this->getFieldsArrayByType(
			$this->getIndexFieldsArraySuffix()
		);
	}

	public function getFieldsArrayByType(string $type) : array
	{
		$configString = "{$this->getPackageConfigName()}.models.{$this->getModelConfigPrefix()}.fieldsGroupsFiles.{$type}";

		if(! $class = config($configString))
			throw new \Exception("Fields group class not found for {$configString}");

		return $class::getFieldsGroup();
	}

	public function getRelatedFieldsArray()
	{
		return $this->getFieldsArrayByType('related');
	}


	public function customizeQuery($query)
	{
		return $query;
	}

    public function getIndexElements()
    {
        $query = $this->getModelClass()::query();

        if($with = $this->getIndexElementsRelationsArray())
            $query->with($with);

        foreach($scopes = $this->getIndexElementsScopesArray() as $scope)
            $query->{$scope}();

	    $query = $this->customizeQuery($query);

        return $query->get();
    }
}