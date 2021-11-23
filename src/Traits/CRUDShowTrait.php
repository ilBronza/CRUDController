<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Traits\CRUDRelationshipsManagerTrait;
use IlBronza\CRUD\Traits\CRUDShowRelationshipsTrait;
use Illuminate\Http\Request;

trait CRUDShowTrait
{
	use CRUDShowRelationshipsTrait;
	use CRUDRelationshipsManagerTrait;

	//edit parameters
	public $standardShowView = 'crud::uikit.show';
	public $showButtons = [];

	/**
	 * get show view name
	 *
	 * if declared an overridden view return it, otherwise return default one
	 *
	 * @return string
	 **/
	public function getShowView()
	{
		if($this->showView ?? false)
			return $this->showView;

		return $this->standardShowView;
	}


	private function checkIfUserCanSee()
	{
		if(! $this->modelInstance->userCanSee(auth()->user()))
			abort(403);
	}

	//TODO mettere i campi visibili per show
	public function shareAllowedFields()
	{
		$allowedFields = $fields = array_keys($this->modelInstance->getAttributes());

		$allowedFields = array_diff($allowedFields, $this->guardedShowDBFields ?? []);
		view()->share('allowedFields', $allowedFields);
	}

	public function shareShowModels()
	{
		view()->share('modelInstance', $this->modelInstance);

		if(isset($this->parentModel))
			view()->share('parentModelInstance', $this->parentModel);
	}

	private function shareShowParameters()
	{
		$this->shareShowModels();
		$this->shareRelationships();
		$this->shareAllowedFields();

		if(in_array('index', $this->allowedMethods))
			view()->share('backToListUrl', $this->getIndexUrl());
	}

	public function setShowButtons()
	{
		$this->buildEditableRelationshipsButtons();
	}

	public function getExtendedShowButtons()
	{
		
	}

	public function shareShowButtons()
	{
		$this->setShowButtons();

		$this->getExtendedShowButtons();

		if(count($this->showButtons))
			view()->share('showButtons', $this->showButtons);
	}

	private function manageEditorRequest(Request $request)
	{
		$pluralModelType = $request->pluralModelType;
		$pluralModelType = 'quantities';

		$modelId = $request->rowId;

		return $this->useSingleRelationRelationshipsManager('show', $pluralModelType, $modelId);
	}

	public function _show($modelInstance)
	{
		$this->modelInstance = $modelInstance;

		$this->checkIfUserCanSee();

		if(request()->ibeditor)
			return $this->manageEditorRequest(request());

		$view = $this->getShowView();

		$this->shareShowParameters();
		$this->shareShowButtons();

		$this->shareExtraViews();

		return view($view);
	}
}