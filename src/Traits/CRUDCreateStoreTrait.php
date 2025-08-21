<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use IlBronza\Form\Facades\Form;

trait CRUDCreateStoreTrait
{
	use CRUDValidateTrait;

	use CRUDCreateTrait;
	use CRUDStoreTrait;

	public function setParentModel(Model $model)
	{
		$this->parentModel = $model;
	}

	public function getParentModel() : ? Model
	{
		return $this->parentModel;
	}

	public function associateParentModel(array $parameters) : array
	{
		$parentModelKey = $this->getParentModel()->getForeignKey();

		$parameters[$parentModelKey] = $this->getParentModel()->getKey();

		return $parameters;
	}

	public function addParentModelAssociationParameter(array $parameters) : array
	{
		if(isset($this->parentModel))
			return $this->associateParentModel($parameters);

		return $parameters;
	}

}