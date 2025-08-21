<?php

namespace IlBronza\CRUD\Traits;

use Exception;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelUpdaterEditor;
use IlBronza\Form\Facades\Form;
use IlBronza\FormField\FormField;
use Illuminate\Http\Request;

use function array_keys;
use function implode;
use function request;

trait CRUDUpdateEditorTrait
{
	use CRUDValidateTrait;

	/**
	 * validate request and update model
	 *
	 * @param  Request  $request  , Model $modelInstance
	 *
	 * @return Response redirect
	 **/
	public function _updateEditor(Request $request)
	{
		if ($this->isToggle($request))
			return $this->manageToggle($request);

		if ($this->isAction($request))
			return $this->manageAction($request);

		return $this->manageUpdateGeneric($request);
	}

	public function getInputRequestExtraData(string $dataName, $default = null)
	{
		return request()->input('fieldExtraData.' . $dataName, $default);
	}

	public function addFieldExtraDataParameters(array $updateParameters)
	{
		$fieldExtraData = request()->input('fieldExtraData', []);

		if (($fieldExtraData['refreshrow'] ?? null) || ($fieldExtraData['ajaxextradata']['refreshRow'] ?? null))
		{
			$updateParameters['ibaction'] = true;
			$updateParameters['action'] = 'refreshRow';
		}

		else if (($fieldExtraData['reloadtable'] ?? null) || ($fieldExtraData['ajaxextradata']['reloadTable'] ?? null))
		{
			$updateParameters['ibaction'] = true;
			$updateParameters['action'] = 'reloadTable';
		}

		else if (($fieldExtraData['reloadalltables'] ?? null) || ($fieldExtraData['ajaxextradata']['reloadalltables'] ?? null) || (request()->reloadalltables))
		{
			$updateParameters['ibaction'] = true;
			$updateParameters['action'] = 'reloadAllTables';
		}

		if ($fieldExtraData['ibaction'] ?? null)
			$updateParameters['ibaction'] = $fieldExtraData['ibaction'];

		return $updateParameters;
	}

	public function manageAction(Request $request)
	{
		$ibaction = $request->ibaction;

		$updateParameters = $this->{$ibaction}($request);

		$updateParameters['success'] = true;
		$updateParameters['update-editor'] = true;
		$updateParameters['field'] = $request->field;
		$updateParameters['ibaction'] = true;

		return $this->returnUpdateParameters($request, $updateParameters);
	}

	public function returnFieldFromEditor(Request $request)
	{
		$field = $request->field;

		$updateParameters['success'] = true;
		$updateParameters[$request->field] = $this->getModel()->$field;
		$updateParameters['value'] = $this->getModel()->$field;
		$updateParameters['fetch-field'] = true;
		$updateParameters['field'] = $request->field;
		$updateParameters['model-id'] = $this->getModel()->getKey();

		return $this->returnUpdateParameters($request, $updateParameters);
	}

	private function isToggle(Request $request)
	{
		return $request->input('toggle', false);
	}

	private function validateToggleRequest(Request $request)
	{
		throw new Exception('Questa roba va buttata via tutta in favore del nuovo javascript che tratta il toggle come un campo normale');

		$this->setUpdateFieldsetsProvider();
		$validationArray = $this->fieldsetsProvider->getValidationParameters();

		// $validationArray = $this->getUpdateEditorValidationArray();

		$parameters = $request->validate([
			'field' => 'string|required|in:' . implode(',', array_keys($validationArray)),
			'value' => 'boolean|nullable'
		]);

		$fieldName = $parameters['field'];
		$value = $this->getToggleValue($parameters, $fieldName);

		return [
			$fieldName => $value
		];
	}

	private function getToggleValue(array $parameters, string $fieldName)
	{
		throw new Exception('Questa roba va buttata via tutta in favore del nuovo javascript che tratta il toggle come un campo normale');

		if (isset($parameters['value']))
			return $parameters['value'];

		if (! $nullable = $this->getInputRequestExtraData('nullable'))
			return ! $this->modelInstance->$fieldName;

		if ((($value = $this->modelInstance->$fieldName) === 0) || ($value === false))
			return null;

		return ! $this->modelInstance->$fieldName;
	}

	private function returnUpdateParameters(Request $request, array $updateParameters)
	{
		$updateParameters = $this->addFieldExtraDataParameters($updateParameters);

		return $updateParameters;
	}

	private function isAction(Request $request)
	{
		return $request->input('ibaction', false);
	}

	private function manageToggle(Request $request)
	{
		throw new Exception('Questa roba va buttata via tutta in favore del nuovo javascript che tratta il toggle come un campo normale');

		$updateParameters = $this->validateToggleRequest($request);

		$this->updateModelInstance($updateParameters, false);

		$updateParameters['success'] = true;
		$updateParameters['update-editor'] = true;
		$updateParameters['field'] = $request->field;
		$updateParameters['toggle'] = true;
		$updateParameters['model-id'] = $this->modelInstance->getKey();

		return $this->returnUpdateParameters($request, $updateParameters);
	}

	private function manageUpdateGeneric(Request $request)
	{
		$this->modelInstance = CrudModelUpdaterEditor::saveByRequest(
			$this->getModel(), $this->getUpdateParametersClass(), $request
		);

		$updateParameters['success'] = true;
		$updateParameters[$request->field] = $request->value;
		$updateParameters['update-editor'] = true;
		$updateParameters['field'] = $request->field;
		$updateParameters['model-id'] = $this->getModel()->getKey();
		$updateParameters['value'] = $this->getModel()->{$request->field};

		$formField = $this->getUpdatingFormFieldInstance($request);

		// $this->updateModelInstance($updateParameters, false);

		// $updateParameters['success'] = true;
		// $updateParameters['update-editor'] = true;
		// $updateParameters['model-id'] = $this->modelInstance->getKey();
		// $updateParameters['value'] = $updateParameters[$request->field];

		if ($formFieldAction = $formField->getEditorAction())
		{
			$updateParameters['ibaction'] = true;
			$updateParameters['action'] = $formFieldAction;
		}

		return $this->returnUpdateParameters($request, $updateParameters);
	}

	private function getUpdatingFormFieldInstance(Request $request) : FormField
	{
		$fieldName = $request->field;

		$formFieldParameters = $this->getUpdatingFormField($fieldName);
		$formFieldParameters['name'] = $fieldName;

		return FormField::createFromArray($formFieldParameters);
	}

	private function getUpdatingFormField(string $fieldName)
	{
		$fieldsets = $this->getFormFieldsets('updateEditor');

		return $this->findFormFieldByName($fieldsets, $fieldName);
	}

	//	public function hasEditorUpdateRequest(Request $request) : bool
	//	{
	//		replaced by hasEditorUpdateRequest in CRUDRequestHelper
	//
	//return $request->input('ib-editor', false);
	//	}

	private function findFormFieldByName($fieldsets, $fieldName)
	{
		foreach ($fieldsets as $fieldset)
		{
			$fields = $this->getFieldsetFields($fieldset);

			foreach ($fields as $_fieldName => $field)
				if ($_fieldName == $fieldName)
					return $field;

			$childrenFieldsets = $this->getFieldsetFieldsets($fieldset);

			if ($formField = $this->findFormFieldByName($childrenFieldsets, $fieldName))
				return $formField;
		}

		return null;
	}

	private function validateUpdateEditorRequest(Request $request)
	{
		throw new Exception('TODO questa è cancellabile ?');
		$this->setUpdateFieldsetsProvider();
		$validationArray = $this->fieldsetsProvider->getValidationParameters();

		// $validationArray = $this->getUpdateEditorValidationArray();

		$parameters = $request->validate([
			'field' => 'string|required|in:' . implode(',', array_keys($validationArray)),
			'value' => $validationArray[$request->field ?? ''] ?? []
		]);

		$fieldName = $parameters['field'];
		$value = $parameters['value'];

		return [
			$fieldName => $value
		];
	}
}