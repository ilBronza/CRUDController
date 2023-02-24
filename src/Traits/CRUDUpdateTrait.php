<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelUpdater;
use IlBronza\CRUD\Traits\CRUDUpdateEditorTrait;
use IlBronza\Form\Helpers\FieldsetsProvider\UpdateFieldsetsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait CRUDUpdateTrait
{
	use CRUDValidateTrait;
	use CRUDUpdateEditorTrait;

	public function getAfterUpdateRoute()
	{
		return false;
	}

	public function checkIfUserCanUpdate()
	{
		if(! $this->modelInstance->userCanUpdate(Auth::user()))
			abort(403);

		if(Auth::id() == 1)
			return true;

		if(! $user = Auth::user())
			return redirect()->to('login');

		if($user->hasRole('superadmin'))
			return true;

		if(! $this->modelInstance->userCanUpdate($user))
			abort(403);
	}

	/**
	 * get after update redirect url
	 *
	 * @return string url
	 */
	public function getAfterUpdatedRedirectUrl()
	{
		if($url = $this->getReturnUrl())
			return $url;

		if($url = $this->getAfterUpdateRoute())
			return $url;

		if($this->isSaveAndNew())
			return $this->getRouteUrlByType('create');

		if($this->isSaveAndRefresh())
			return $this->getRouteUrlByType('edit');

		if(in_array('index', $this->allowedMethods))
			return $this->getRouteUrlByType('index');

		if(in_array('show', $this->allowedMethods))
			return $this->getRouteUrlByType('show');

		return url()->previous();
	}

	public function parseUniqueRules(array $rules) : array
	{
		foreach($rules as $field => $rule)
		{
			if(is_string($rule))
				$rule = explode("|", $rule);

			foreach($rule as $index => $_rule)
				if(strpos($_rule, "unique:") !== false)
				{
					// $rule[$index] = implode(",", [
					// 	$rule[$index],
					// 	// $this->modelInstance->getKeyName(),
					// 	$this->modelInstance->getKeyName(),
					// 	$this->modelInstance->getKey()
					// ]);

					$rule[$index] = \Illuminate\Validation\Rule::unique($this->modelInstance->getTable())->ignore($this->modelInstance->getKey(), $this->modelInstance->getKeyName());

					$rules[$field] = $rule;
				}
		}

		return $rules;
	}

	/**
	 * get update validation array
	 *
	 * @return array
	 **/
	public function getUpdateValidationArray()
	{
		$result = $this->getValidationArrayByType('update');

		return $this->parseUniqueRules($result);
	}

	public function getUpdateEditorValidationArray()
	{
		$result = $this->getValidationArrayByType('updateEditor');

		return $this->parseUniqueRules($result);
	}

	/**
	 * validate request and return requested values for update
	 **/
	private function validateUpdateRequest(Request $request)
	{
		return $this->validateRequestByType($request, 'update');
	}

	public function manageModelInstanceAfterUpdate(array $parameters)
	{

	}

	/**
	 * update model instance with given array parameters
	 *
	 * @param array $parameters
	 * @return boolean
	 **/
	public function updateModelInstance(array $parameters, bool $cleanRelationships = true)
	{
		if($cleanRelationships)
			$parameters = $this->cleanParametersFromRelationshipsByType($parameters, 'update');

		//this way I don't need to set fillable parameters
		// $this->modelInstance->fill($parameters);
		foreach($parameters as $property => $value)
			$this->modelInstance->{$property} = $value;

		$this->manageModelInstanceAfterUpdate($parameters);

		return $this->modelInstance->save();
	}

	/**
	 * send update success message
	 **/
	public function sendUpdateSuccessMessage()
	{

	}

	public function manageAfterUpdate(Request $request)
	{
		
	}

	public function initializeUpdateFieldsetsProvider() : UpdateFieldsetsProvider
	{
		if($file = $this->getUpdateParametersClass())
			return UpdateFieldsetsProvider::setFieldsetsParametersByFile(
					$file,
					$this->modelInstance
				);

		return UpdateFieldsetsProvider::setFieldsetsParametersByArray(
				$this->getFormFieldsets('edit'),
				$this->modelInstance
			);
	}

	public function setUpdateFieldsetsProvider()
	{
		$this->fieldsetsProvider = $this->initializeUpdateFieldsetsProvider();
	}

	public function getUpdateFieldsetsProvider() : UpdateFieldsetsProvider
	{
		if($this->fieldsetsProvider ?? false)
			return $this->fieldsetsProvider;

		return $this->setUpdateFieldsetsProvider();
	}

	public function getValidatedUpdateParameters(Request $request) : array
	{
		$this->setUpdateFieldsetsProvider();

		return $request->validate(
			$this->fieldsetsProvider->getValidationParameters()
		);
	}

	/**
	 * validate request and update model
	 *
	 * @param Request $request, Model $modelInstance
	 * @return Response redirect
	 **/
	public function _update(Request $request, $modelInstance)
	{
		$this->modelInstance = $modelInstance;
		$this->checkIfUserCanUpdate();

		if($this->hasEditorUpdateRequest($request))
			return $this->_updateEditor($request);

		if($this->hasFileUploadRequest($request))
			return $this->_uploadFile($request, 'update');

		$this->modelInstance = CrudModelUpdater::saveByRequest(
			$modelInstance,
			$this->getUpdateParametersClass(),
			$request
		);

		$this->sendUpdateSuccessMessage();

		return redirect()->to(
			$this->getAfterUpdatedRedirectUrl()
		);
	}
}