<?php

namespace IlBronza\CRUD\Helpers\ModelManagers\Traits;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use Illuminate\Database\Eloquent\Model;

trait ClonableModelTrait
{
	public function getCloneUrl() : string
	{
		return $this->getKeyedRoute('clone', []);
	}
}