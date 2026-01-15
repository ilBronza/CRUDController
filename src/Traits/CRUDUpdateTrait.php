<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\CrudRequestHelper;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelUpdater;
use IlBronza\CRUD\Traits\CRUDUpdateEditorTrait;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\UpdateFieldsetsProvider;
use IlBronza\Ukn\Ukn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function dd;

trait CRUDUpdateTrait
{
	use CRUDValidateTrait;
	use CRUDUpdateEditorTrait;

	public function getAfterUpdateRoute()
	{
		return false;
		// return $this->getRouteUrlByType('show');
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
			return $this->getCreateUrl();

		if($this->isSaveAndRefresh())
			return $this->getRouteUrlByType('edit');

		if(in_array('index', $this->allowedMethods))
			return $this->getRouteUrlByType('index');

		if(in_array('show', $this->allowedMethods))
			return $this->getRouteUrlByType('show');

		if(url()->previous() == $this->getModel()->getEditUrl())
			return $this->getModel()->getIndexUrl();

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
		{
			$setterName = 'set' . Str::studly($property);

			if(method_exists($this->modelInstance, $setterName))
				$this->modelInstance->{$setterName}($value);

			else
			{
				Log::critical('dichiara ' . $setterName . ' su ' . get_class($this->modelInstance));
				$this->modelInstance->{$property} = $value;
			}
		}

		$this->manageModelInstanceAfterUpdate($parameters);

		return $this->modelInstance->save();
	}

	/**
	 * send update success message
	 **/
	public function sendUpdateSuccessMessage()
	{
		Ukn::s(trans('crud::messages.successfullyUpdated', [
			'modelClass' => $this->getModel()->getTranslatedClassname(),
			'model' => $this->getModel()->getName()
		]));
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

	public function getFieldsetsProvider() : FieldsetsProvider
	{
		return $this->fieldsetsProvider;
	}

	public function getValidatedUpdateParameters(Request $request) : array
	{
		$this->setUpdateFieldsetsProvider();

		return $request->validate(
			$this->fieldsetsProvider->getValidationParameters()
		);
	}

	public function getUpdateCallback() : ? callable
	{
		return null;
	}

	public function getUpdateEvents() : array
	{
		return $this->updateEvents ?? [];
	}

	public function getUpdaterHelperClassName() : string
	{
		return CrudModelUpdater::class;
	}

	/**
	 * validate request and update model
	 *
	 * @param Request $request, Model $modelInstance
	 * @return Response redirect
	 **/
	public function _update(Request $request, $modelInstance)
	{
		$this->setModel($modelInstance);

		$this->checkIfUserCanUpdate();

//		if($this->hasEditorUpdateRequest($request))
		if(CrudRequestHelper::isEditorUpdateRequest($request))
			return $this->_updateEditor($request);

		if(CrudRequestHelper::isEditorReadRequest($request))
			return $this->returnFieldFromEditor($request);

		//		if($this->hasFileUploadRequest($request))
		if(CrudRequestHelper::isFileUploadRequest($request))
			return $this->_uploadFile($request, 'update');

		$this->modelInstance = $this->getUpdaterHelperClassName()::saveByRequest(
			$this->getModel(),
			$this->getUpdateParametersClass(),
			$request,
			$this->getUpdateEvents(),
			$this->getUpdateCallback()
		);

		$this->sendUpdateSuccessMessage();

		if(CrudRequestHelper::isSaveAndCopy($request))
			return redirect()->to($this->modelInstance->getEditUrl());

		return redirect()->to(
			$this->getAfterUpdatedRedirectUrl()
		);
	}
}