<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\ValidateUpdateFieldsetsProvider;

use function dd;
use function is_null;

class CrudModelBulkUpdater extends CrudModelUpdater
{
	public function getValidationParameters() : array
	{
		$result = $this->getFieldsetsProvider()->getValidationParameters();

		foreach($result as $key => $value)
			$result['bulk_empty_value_' . $key] = ['boolean', 'nullable'];

		return $result;
	}

	public function validateParameters(array $validationParamters) : array
	{
		$parameters = $this->getRequest()->validate(
			$validationParamters
		);

		foreach($parameters as $key => $value)
			if((is_null($value))&&(($parameters['bulk_empty_value_' . $key] ?? false) == false))
				unset($parameters[$key]);

		return $parameters;
	}
}