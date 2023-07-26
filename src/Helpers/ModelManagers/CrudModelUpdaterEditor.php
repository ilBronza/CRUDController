<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CrudModelUpdaterEditor extends CrudModelUpdater
{
	public function sanitizeParametersAndValues(array $parameters) : array
	{
		return [
			$parameters['field'] => $parameters['value']
		];
	}

	public function getValidationParameters() : array
	{
		$validationArray = $this->getFieldsetsProvider()->getValidationParameters();

		return [
			'field' => 'string|required|in:' . implode(",", array_keys($validationArray)),
			'value' => $validationArray[$this->getRequest()->field ?? ''] ?? []
		];
	}

}