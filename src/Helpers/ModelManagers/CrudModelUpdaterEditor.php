<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CrudModelUpdaterEditor extends CrudModelUpdater
{
	public static function saveBatchByRequest(
		Model $model,
		FieldsetParametersFile $parametersFile,
		Request $request,
		array $events = [],
		?callable $callback = null
	) : Model
	{
		$helper = static::create($model, $parametersFile, $request);
		$result = $helper->bindBatchRequest($request);

		$helper->fireEvents($events);

		if ($callback)
			$callback();

		return $result;
	}

	public function bindBatchRequest(Request $request) : Model
	{
		$this->setRequest($request);
		$this->setFieldsetsProvider();

		$fields = $request->validate([
			'fields' => ['required', 'array', 'min:1'],
		])['fields'];
		$validationArray = $this->getFieldsetsProvider()->getValidationParameters();
		$parameters = [];
		$errors = [];

		foreach ($fields as $field => $value)
		{
			$fieldName = (string) $field;
			$errorKey = 'fields.' . $fieldName;

			if (! is_string($field) || ! array_key_exists($field, $validationArray))
			{
				$errors[$errorKey][] = trans('validation.in', [
					'attribute' => $fieldName,
				]);

				continue;
			}

			$validator = Validator::make([
				'value' => $value,
			], [
				'value' => $validationArray[$field],
			], [], [
				'value' => $fieldName,
			]);

			if ($validator->fails())
			{
				$errors[$errorKey] = $validator->errors()->all();

				continue;
			}

			$parameters[$field] = $value;
		}

		if ($errors)
			throw ValidationException::withMessages($errors);

		return $this->bindParameters($parameters);
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
