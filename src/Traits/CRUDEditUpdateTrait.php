<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ilBronza\Form\Facades\Form;

trait CRUDEditUpdateTrait
{
	use CRUDFormTrait;
	use CRUDValidateTrait;

	//edit parameters
	public $editView;
	public $standardEditView = 'form::uikit.form';

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
            $this->modelInstance->{$fieldName} = [];

            $elements = $this->modelInstance->{$relation}()->get();

            if(count($elements) == 0)
                continue;

            $this->modelInstance->{$fieldName} = $elements->pluck(
                $elements->first()->getKeyName()
            )->toArray();
        }
    }

    public function loadEditExtraViews()
    {
    	
    }

	/**
	 * get modelInstance edit form
	 *
	 * @return view
	 **/
	public function _edit($modelInstance)
	{
		$this->modelInstance = $modelInstance;

		$this->checkIfUserCanUpdate();

		$view = $this->getEditView();

		if($view == $this->standardEditView)
			$this->shareDefaultEditFormParameters();

		$this->setFormParametersByType('edit');

		$this->loadEditRelationshipsValues();
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
		if(in_array('index', $this->allowedMethods))
			return $this->getRouteUrlByType('index');

		if(in_array('show', $this->allowedMethods))
			return $this->getRouteUrlByType('show');

		return url()->previous();
	}

	/**
	 * get update validation array
	 *
	 * @return array
	 **/
	public function getUpdateValidationArray()
	{
		return $this->getValidationArrayByType('update');
	}

	/**
	 * validate request and return requested values for update
	 **/
	private function validateUpdateRequest(Request $request)
	{
		return $this->validateRequestByType($request, 'update');
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

		$this->modelInstance->fill($parameters);
		return $this->modelInstance->save();		
	}

	/**
	 * send update success message
	 **/
	public function sendUpdateSuccessMessage()
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

		$parameters = $this->validateUpdateRequest($request);

		$this->updateModelInstance($parameters);

		$this->associateRelationshipsByType($parameters, 'update');

		$this->sendUpdateSuccessMessage();

		return redirect()->to(
			$this->getAfterUpdatedRedirectUrl()
		);
	}
}