<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use IlBronza\CRUD\Traits\CRUDUpdateEditorTrait;

trait CRUDEditUpdateTrait
{
	use CRUDValidateTrait;
	use CRUDUpdateEditorTrait;

	//edit parameters
	public $editView;
	// public $standardEditView = 'form::uikit.form';
	public $standardEditView = 'crud::uikit.edit';

	public function getAfterUpdateRoute()
	{
		return false;
	}

	/**
	 * get edit view name
	 *
	 * if declared an overridden view return it, otherwise return default one
	 *
	 * @return string
	 **/
	public function getEditView()
	{
		if($this->editView)
			return $this->editView;

		return $this->standardEditView;
	}

	/**
	 * get update model action form update form
	 *
	 * return [].update route with given model instance key
	 *
	 * @return string
	 **/
	public function getUpdateModelAction()
	{
		return $this->getRouteUrlByType('update');
	}

	/**
	 * share parameters to populate edit view
	 *
	 * @return callable
	 **/
	public function shareDefaultEditFormParameters()
	{
		return $this->shareDefaultFormParameters('edit');
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

	public function loadEditRelationshipsValues()
	{
        foreach($this->relatedFields ?? [] as $relation => $fieldName)
        {
            $elements = $this->modelInstance->{$relation}()->get();

            $this->modelInstance->{$fieldName} = [];

            if(count($elements) == 0)
                continue;

            $this->modelInstance->{$fieldName} = $elements->pluck(
                $elements->first()->getKeyName()
            )->toArray();
        }
    }

	public function getExtendedEditButtons()
	{
	}

	public function shareEditButtons()
	{
		$this->getExtendedEditButtons();

		if((isset($this->editButtons))&&(count($this->editButtons)))
			view()->share('buttons', $this->editButtons);
	}

	public function addEditExtraViews()
	{
		
	}

    public function loadEditExtraViews()
    {
    	$this->addEditExtraViews();
        $this->shareExtraViews();
    }

	/**
	 * get modelInstance edit form
	 *
	 * @return view
	 **/
	public function _edit($modelInstance)
	{
		$this->manageReturnBack();
		$this->modelInstance = $modelInstance;

		$this->checkIfUserCanUpdate();

		$view = $this->getEditView();

		if($view == $this->standardEditView)
			$this->shareDefaultEditFormParameters();

		$this->setFormParametersByType('edit');

		$this->loadEditRelationshipsValues();
		$this->shareEditButtons();
		$this->loadEditExtraViews();

		if(method_exists($this, 'beforeRenderEdit'))
			$this->beforeRenderEdit();

		return view($view);
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
					$rule[$index] = implode(",", [
						$rule[$index],
						// $this->modelInstance->getKeyName(),
						$this->modelInstance->getKey()
					]);

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
	public function updateModelInstance(array $parameters)
	{
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

		$parameters = $this->validateUpdateRequest($request);
		$parameters = $this->transformParametersByFieldsAndType($parameters, 'update');

		$this->updateModelInstance($parameters);

		if(method_exists($this, 'associateRelationshipsByType'))
			$this->associateRelationshipsByType($parameters, 'update');

		$this->modelInstance->save();

		$this->sendUpdateSuccessMessage();
		$this->manageAfterUpdate($request);

		return redirect()->to(
			$this->getAfterUpdatedRedirectUrl()
		);
	}
}