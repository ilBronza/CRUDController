<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use IlBronza\Form\Facades\Form;

trait CRUDStoreTrait
{
	use CRUDFormTrait;
	use CRUDValidateTrait;

	/**
	 * get after store redirect url
	 *
	 * @return string url
	 */
	public function getAfterStoredRedirectUrl()
	{
		return $this->getRouteUrlByType('index');
	}

	/**
	 * get store validation array
	 *
	 * @return array
	 **/
	public function getStoreValidationArray()
	{
		return $this->getValidationArrayByType('store');
	}

	/**
	 * validate request and return requested values for store
	 **/
	private function validateStoreRequest(Request $request)
	{
		return $this->validateRequestByType($request, 'store');
	}

	public function setBeforeStoreFields(array $parameters) { }

	/**
	 * store model instance with given array parameters
	 *
	 * @param array $parameters
	 * @return boolean
	 **/
	public function bindModelInstance(array $parameters)
	{
		$parameters = array_diff_key($parameters, $this->getRelationshipsFieldsByType('store'));

		foreach($parameters as $name => $value)
			$this->modelInstance->$name = $value;

		$this->setBeforeStoreFields($parameters);

		//removed becaus foreign keys not set jet, when not nullable a mysql error is returned
		// $this->modelInstance->save();
	}

	/**
	 * send store success message
	 **/
	public function sendStoreSuccessMessage()
	{

	}

	/**
	 * validate request and store model
	 *
	 * @param Request $request, Model $modelInstance
	 * @return Response redirect
	 **/
	public function store(Request $request)
	{
		return $this->_store($request);
	}

	/**
	 * validate request and store model
	 *
	 * @param Request $request, Model $modelInstance
	 * @return Response redirect
	 **/
	public function _store(Request $request)
	{
		$parameters = $this->validateStoreRequest($request);

		$parameters = $this->transformParametersByFieldsAndType($parameters, 'store');

		$this->modelInstance = new $this->modelClass;

		$this->manageParentModelAssociation();

		$this->bindModelInstance($parameters);

		$this->associateRelationshipsByType($parameters, 'store');

		$this->modelInstance->save();

		$this->sendStoreSuccessMessage();

		return redirect()->to(
			$this->getAfterStoredRedirectUrl()
		);
	}
}