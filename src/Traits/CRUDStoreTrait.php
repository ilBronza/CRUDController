<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use IlBronza\Form\Facades\Form;

trait CRUDStoreTrait
{
	use CRUDValidateTrait;

	/**
	 * get after store redirect url
	 *
	 * @return string url
	 */
	public function getAfterStoredRedirectUrl()
	{
		if($this->isSaveAndNew())
			return $this->getRouteUrlByType('create');

		if($this->isSaveAndRefresh())
			return $this->getRouteUrlByType('edit');

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
	public function bindModelInstance(array $originalParameters)
	{
		$parameters = array_diff_key($originalParameters, $this->getRelationshipsFieldsByType('store'));

		$foreignKeyFields = array_intersect_key($originalParameters, $this->getForeignKeysFieldsByType('store'));

		foreach($foreignKeyFields as $name => $value)
			$this->modelInstance->$name = $value;

		$foreignKeyRelationships = array_intersect_key($originalParameters, $this->getForeignRelationshipsFieldsByType('store'));

		foreach($foreignKeyRelationships as $name => $value)
			$this->modelInstance->{$name}()->associate($value);

		foreach($parameters as $name => $value)
			$this->modelInstance->$name = $value;

		$this->setBeforeStoreFields($parameters);

		//NON RIMUOVERE PERCHE' LE MANY TO MANY NON SI SALVANO, SERVE UN ID PER LAVORARE
		//METTERE LE FOREIGN NULLABLE
		//removed becaus foreign keys not set jet, when not nullable a mysql error is returned
		$this->modelInstance->save();
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

		$this->modelInstance = $this->modelClass::make();

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