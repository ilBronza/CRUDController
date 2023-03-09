<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelCreator;
use IlBronza\Form\Facades\Form;
use IlBronza\Form\Helpers\FieldsetsProvider\CreateFieldsetsProvider;
use Illuminate\Http\Request;

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
	public function userCanPerformCreate() {}
	public function manageBeforeCreate() {
		$this->shareCreateButtons();
		$this->loadCreateExtraViews();		
	}

	/**
	 * get modelInstance create view
	 *
	 * @return view
	 **/
	public function create()
	{
		$this->setModel(
			$this->makeModel()
		);

		$this->userCanPerformCreate();
		$this->manageReturnBack();

		$this->modelFormHelper = CrudModelCreator::buildForm(
			$this->getModel(),
			$this->getCreateParametersClass(),
			$this->getStoreModelAction(),
			$this->provideFormDefaultSettings(),
		);

		$this->manageBeforeCreate();

		return $this->modelFormHelper->render();
	}
}