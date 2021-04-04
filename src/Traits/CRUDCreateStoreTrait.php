<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use ilBronza\Form\Facades\Form;

trait CRUDCreateStoreTrait
{
	use CRUDFormTrait;
	use CRUDValidateTrait;

	use CRUDCreateTrait;
	use CRUDStoreTrait;

	public function manageParentModelAssociation()
	{
		if(isset($this->parentModel))
			$this->associateParentModel();
	}

}