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
		$validationArray = $this->getUpdateValidationArray();

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
		$validationArray = $this->getUpdateValidationArray();

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

	private function manageToggle(Request $request)
	{
		$updateParameters = $this->validateToggleRequest($request);

		$this->updateModelInstance($updateParameters);

		$updateParameters['success'] = true;
		$updateParameters['update-editor'] = true;
		$updateParameters['toggle'] = true;
		$updateParameters['model-id'] = $this->modelInstance->getKey();

		return $updateParameters;
	}

	public function manageAction(Request $request)
	{
		$ibaction = $request->ibaction;

		$updateParameters = $this->{$ibaction}($request);

		$updateParameters['success'] = true;
		$updateParameters['update-editor'] = true;
		$updateParameters['ibaction'] = true;

		return $updateParameters;
	}

	private function manageUpdateGeneric(Request $request)
	{		
		$updateParameters = $this->validateUpdateEditorRequest($request);

		$this->updateModelInstance($updateParameters);

		$updateParameters['success'] = true;
		$updateParameters['update-editor'] = true;
		$updateParameters['value'] = $updateParameters[$request->field];

		return $updateParameters;
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
	public function _updateEditor(Request $request, $modelInstance)
	{
		$this->modelInstance = $modelInstance;

		$this->checkIfUserCanUpdate();

		if($this->isToggle($request))
			return $this->manageToggle($request);

		if($this->isAction($request))
			return $this->manageAction($request);

		return $this->manageUpdateGeneric($request);

		mori("NOTOGGLE SIGNO'");

		mori($parameters);

		$this->updateModelInstance($parameters);

		if(method_exists($this, 'associateRelationshipsByType'))
			$this->associateRelationshipsByType($parameters, 'update');

		$this->sendUpdateSuccessMessage();

		return redirect()->to(
			$this->getAfterUpdatedRedirectUrl()
		);
	}
}