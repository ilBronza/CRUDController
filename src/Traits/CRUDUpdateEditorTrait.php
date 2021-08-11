<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use IlBronza\Form\Facades\Form;

trait CRUDUpdateEditorTrait
{
	use CRUDValidateTrait;

	private function getToggleValue(array $parameters, string $fieldName)
	{
		if(isset($parameters['value']))
			return $parameters['value'];

		return  ! $this->modelInstance->$fieldName;
	}

	private function validateUpdateEditorRequest(Request $request)
	{
		$validationArray = $this->getUpdateEditorValidationArray();

		$parameters = $request->validate([
			'field' => 'string|required|in:' . implode(",", array_keys($validationArray)),
			'value' => $validationArray[$request->field ?? ''] ?? []
		]);

		$fieldName = $parameters['field'];
		$value = $parameters['value'];

		return [
			$fieldName => $value
		];
	}

	private function validateToggleRequest(Request $request)
	{
		$validationArray = $this->getUpdateEditorValidationArray();

		$parameters = $request->validate([
			'field' => 'string|required|in:' . implode(",", array_keys($validationArray)),
			'value' => 'boolean|nullable'
		]);

		$fieldName = $parameters['field'];
		$value = $this->getToggleValue($parameters, $fieldName);

		return [
			$fieldName => $value
		];
	}

	private function isToggle(Request $request)
	{
		return $request->input('toggle', false);
	}

	private function isAction(Request $request)
	{
		return $request->input('ibaction', false);		
	}

	private function returnUpdateParameters(Request $request, array $updateParameters)
	{
		$fieldExtraData = $request->input('fieldExtraData', []);

		if($fieldExtraData['refreshrow'] ?? null)
		{
			$updateParameters['ibaction'] = true;
			$updateParameters['action'] = 'refreshRow';
		}

		return $updateParameters;
	}

	private function manageToggle(Request $request)
	{
		$updateParameters = $this->validateToggleRequest($request);

		$this->updateModelInstance($updateParameters);

		$updateParameters['success'] = true;
		$updateParameters['update-editor'] = true;
		$updateParameters['toggle'] = true;
		$updateParameters['model-id'] = $this->modelInstance->getKey();

		return $this->returnUpdateParameters($request, $updateParameters);
	}

	public function manageAction(Request $request)
	{
		$ibaction = $request->ibaction;

		$updateParameters = $this->{$ibaction}($request);

		$updateParameters['success'] = true;
		$updateParameters['update-editor'] = true;
		$updateParameters['ibaction'] = true;

		return $this->returnUpdateParameters($request, $updateParameters);
	}

	private function manageUpdateGeneric(Request $request)
	{
		$updateParameters = $this->validateUpdateEditorRequest($request);

		$this->updateModelInstance($updateParameters);

		$updateParameters['success'] = true;
		$updateParameters['update-editor'] = true;
		$updateParameters['model-id'] = $this->modelInstance->getKey();
		$updateParameters['value'] = $updateParameters[$request->field];

		return $this->returnUpdateParameters($request, $updateParameters);
	}

	public function hasEditorUpdateRequest(Request $request) : bool
	{
		return $request->input('ib-editor', false);
	}

	/**
	 * validate request and update model
	 *
	 * @param Request $request, Model $modelInstance
	 * @return Response redirect
	 **/
	public function _updateEditor(Request $request)
	{
		if($this->isToggle($request))
			return $this->manageToggle($request);

		if($this->isAction($request))
			return $this->manageAction($request);

		return $this->manageUpdateGeneric($request);
	}
}