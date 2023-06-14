<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\ValidateStoreFieldsetsProvider;

class CrudModelStorer extends CrudModelStoringHelper
{
	public function initializeFieldsetsProvider() : FieldsetsProvider
	{
		return ValidateStoreFieldsetsProvider::setFieldsetsParametersByFile(
				$this->getFieldsetParametersFile(),
				$this->getModel()
			);
	}
}