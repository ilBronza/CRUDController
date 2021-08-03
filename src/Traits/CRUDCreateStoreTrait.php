<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use IlBronza\Form\Facades\Form;

trait CRUDCreateStoreTrait
{
	use CRUDValidateTrait;

	use CRUDCreateTrait;
	use CRUDStoreTrait;

	public function manageParentModelAssociation()
	{
		if(isset($this->parentModel))
			$this->associateParentModel();
	}

}