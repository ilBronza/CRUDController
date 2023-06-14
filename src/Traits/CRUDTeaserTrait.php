<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Traits\CRUDFileParametersTrait;
use IlBronza\Form\Helpers\FieldsetsProvider\ShowFieldsetsProvider;

trait CRUDTeaserTrait
{
	use CRUDFileParametersTrait;

	//edit parameters
	public $teaserView = 'crud::uikit._teaser';

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
		view()->share('modelInstance', $this->getModel());
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


	public function getTeaserFieldsets()
	{
		if($file = $this->getTeaserParametersClass())
			return ShowFieldsetsProvider::getFieldsetsCollectionByParametersFile(
				$file,
				$this->getModel()
			);

		throw new \Exception('Set a teaser parameters class');
	}


	public function _teaser($modelInstance)
	{
		$this->setModel($modelInstance);

		$this->checkIfUserCanSeeTeaser();

		$view = $this->getTeaserView();

		$this->shareTeaserParameters();

		//remove All old fieldsets modes
		return view($view, [
			'modelInstance' => $this->getModel(),
			'fieldsets' => $this->getTeaserFieldsets()
		]);
	}
}