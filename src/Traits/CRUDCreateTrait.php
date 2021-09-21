<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use IlBronza\Form\Facades\Form;

trait CRUDCreateTrait
{
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
		return $this->getRouteUrlByType('store');
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
	 * overrideable method to manage modelInstance before view rendering
	 **/
	public function beforeRenderCreate() { }

	public function userCanPerformCreate() { }

	/**
	 * get modelInstance create view
	 *
	 * @return view
	 **/
	public function create()
	{
		$this->userCanPerformCreate();
		$this->manageReturnBack();

		$this->modelInstance = new $this->modelClass;

		$this->manageParentModelAssociation();

		$view = $this->getCreateView();

		if($view == $this->standardCreateView)
			$this->shareDefaultCreateFormParameters();

		$this->beforeRenderCreate();

		return view($view);
	}
}