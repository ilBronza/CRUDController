<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use IlBronza\Form\Facades\Form;

trait CRUDCreateStoreTrait
{
	use CRUDValidateTrait;

	use CRUDCreateTrait;
	use CRUDStoreTrait;

	public function addParentModelAssociationParameter(array $parameters) : array
	{
		if(isset($this->parentModel))
			return $this->associateParentModel($parameters);

		return $parameters;
	}

}