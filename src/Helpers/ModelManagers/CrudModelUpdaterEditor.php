<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CrudModelUpdaterEditor extends CrudModelUpdater
{
	public function getValidatedRequestParameters() : array
	{
		if (! $this->getRequest()->boolean('ib-editor-batch'))
			return parent::getValidatedRequestParameters();

		$this->setFieldsetsProvider();

		$fields = $this->validateParameters([
			'fields' => ['required', 'array', 'min:1'],
		])['fields'];

		$validationParameters = $this->getFieldsetsProvider()->getValidationParameters();
		$allowedFields = [];

		foreach (array_keys($validationParameters) as $fieldName)
			$allowedFields[] = explode('.', $fieldName)[0];

		foreach (array_keys($fields) as $fieldName)
			if (! in_array($fieldName, $allowedFields, true))
				throw ValidationException::withMessages([
					'fields.' . $fieldName => ['Il campo non è modificabile dall\'editor inline.'],
				]);

		$batchValidationParameters = [];

		foreach ($validationParameters as $fieldName => $rules)
			if (array_key_exists(explode('.', $fieldName)[0], $fields))
				$batchValidationParameters[$fieldName] = $rules;

		$parameters = Validator::make($fields, $batchValidationParameters)->validate();

		return parent::sanitizeParametersAndValues($parameters);
	}

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
