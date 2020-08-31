<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use ilBronza\Form\Facades\Form;

trait CRUDCreateStoreTrait
{
	use CRUDFormTrait;
	use CRUDValidateTrait;

	public $createView;
	public $standardCreateView = 'form::uikit.form';

	/**
	 * get create view name
	 *
	 * if declared an overridden view return it, otherwise return default one
	 *
	 * @return string
	 **/
	public function getCreateView()
	{
		if($this->createView)
			return $this->createView;

		return $this->standardCreateView;
	}

	/**
	 * get store model action form store form
	 *
	 * return [].store route with given model instance key
	 *
	 * @return string
	 **/
	public function getStoreModelAction()
	{
		$actionString = implode(".", [
			$this->getPluralModelClassname($this->modelInstance),
			'store'
		]);

		return route($actionString, [$this->modelInstance]);
	}

	/**
	 * share parameters to populate create view
	 *
	 * @return callable
	 **/
	public function shareDefaultCreateFormParameters()
	{
		return $this->shareDefaultFormParameters('create');
	}

	/**
	 * get modelInstance create view
	 *
	 * @return view
	 **/
	public function create()
	{
		$this->modelInstance = new $this->modelClass;
		$view = $this->getCreateView();

		if($view == $this->standardCreateView)
			$this->shareDefaultCreateFormParameters();

		return view($view);
	}

	/**
	 * get after store redirect url
	 *
	 * @return string url
	 */
	public function getStoredRedirectUrl()
	{
		$actionString = implode(".", [
			$this->getPluralModelClassname($this->modelInstance),
			'index'
		]);

		return route($actionString);
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

	/**
	 * store model instance with given array parameters
	 *
	 * @param array $parameters
	 * @return boolean
	 **/
	public function storeModelInstance(array $parameters)
	{
		$this->modelInstance->fill($parameters);
		return $this->modelInstance->save();		
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
		$parameters = $this->validateStoreRequest($request);

		$this->modelInstance = new $this->modelClass;

		$this->storeModelInstance($parameters);

		$this->associateRelationshipsByType($parameters, 'store');

		$this->sendStoreSuccessMessage();

		return redirect()->to(
			$this->getStoredRedirectUrl()
		);
	}
}