<?php

namespace IlBronza\CRUD\Traits;

trait CRUDTeaserTrait
{
	//edit parameters
	public $teaserView = 'crud::uikit.teaser';

	/**
	 * get teaser view name
	 *
	 * @return string
	 **/
	public function getTeaserView()
	{
		return $this->teaserView;
	}

	private function checkIfUserCanSeeTeaser()
	{
		if(! $this->modelInstance->userCanSeeTeaser(auth()->user()))
			abort(403);
	}

	private function getGuardedTeaserDBFields()
	{
		return $this->guardedTeaserDBFields ?? [];
	}

	//TODO mettere i campi visibili per show
	public function shareAllowedTeaserFields()
	{
		$allowedFields = $fields = array_keys($this->modelInstance->getAttributes());

		$allowedFields = array_diff($allowedFields, $this->getGuardedTeaserDBFields());
		view()->share('allowedFields', $allowedFields);
	}

	public function shareTeaserModels()
	{
		view()->share('modelInstance', $this->modelInstance);
	}

	// public function getEditModelIsntanceUrl()
	// {
	// 	return null;
	// }

	private function shareTeaserParameters()
	{
		$this->shareTeaserModels();

		$this->shareAllowedTeaserFields();
	}

	// public function setShowButtons()
	// {
	// 	$this->buildEditableRelationshipsButtons();
	// }

	// public function getExtendedShowButtons()
	// {
	// }

	// public function shareShowButtons()
	// {
	// 	$this->getExtendedShowButtons();

	// 	$this->setShowButtons();

	// 	if(count($this->showButtons))
	// 		view()->share('showButtons', $this->showButtons);
	// }

	// private function manageEditorRequest(Request $request)
	// {
	// 	$pluralModelType = $request->modelName;
	// 	// $pluralModelType = 'quantities';

	// 	$modelId = $request->rowId;

	// 	return $this->useSingleRelationRelationshipsManager('show', $pluralModelType, $modelId);
	// }

	public function _teaser($modelInstance)
	{
		$this->modelInstance = $modelInstance;

		$this->checkIfUserCanSeeTeaser();

		$view = $this->getTeaserView();

		$this->shareTeaserParameters();

		return view($view);
	}
}