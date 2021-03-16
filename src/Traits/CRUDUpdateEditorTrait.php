<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ilBronza\Form\Facades\Form;

trait CRUDUpdateEditorTrait
{
	use CRUDValidateTrait;

	private function getToggleValue(array $parameters, string $fieldName)
	{
		if(isset($parameters['value']))
			return $parameters['value'];

		return  ! $this->modelInstance->$fieldName;
	}

	private function validateToggleRequest(Request $request)
	{
		$validationArray = $this->getUpdateValidationArray();

		$parameters = $request->validate([
			'field' => 'string|required|in:' . implode(",", array_keys($validationArray)),
			'value' => 'boolean|nullable'
		]);

		$fieldName = $parameters['field'];
		$toggleValue = $this->getToggleValue($parameters, $fieldName);

		return [
			$fieldName => $toggleValue
		];
	}

	private function isToggle(Request $request)
	{
		return $request->input('toggle', false);
	}

	private function manageToggle(Request $request)
	{
		$updateParameters = $this->validateToggleRequest($request);

		$this->updateModelInstance($updateParameters);

		$updateParameters['success'] = true;
		$updateParameters['toggle'] = true;

		return $updateParameters;
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


		$parameters = $this->validateUpdateEditorRequest($request);

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