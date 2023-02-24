<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\ValidateUpdateFieldsetsProvider;

class CrudModelUpdater extends CrudModelStoringHelper
{
	public function initializeFieldsetsProvider() : FieldsetsProvider
	{
		return ValidateUpdateFieldsetsProvider::setFieldsetsParametersByFile(
				$this->getFieldsetParametersFile(),
				$this->getModel()
			);
	}
}