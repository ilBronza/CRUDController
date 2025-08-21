<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelBulkEditor;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelBulkUpdater;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelUpdater;
use Illuminate\Http\Request;

use function redirect;

trait CRUDBulkEditTrait
{
	public function bulkEdit(Request $request)
	{
		$this->setKeys($request);

		return $this->_bulkEdit();
	}


	public function setKeys(Request $request)
	{
		$this->validateBulkKeys($request);

		$this->keys = $request->ids;
	}

	public function loadBulkEditExtraViews()
	{
		//usare il trait delle extra views
	}

	public function _bulkEdit()
	{
		$this->setModel(
			$this->getPlaceholderModel()
		);

		$this->checkIfUserCanUpdate();

		$this->shareExtraParameters();

		$this->modelFormHelper = CrudModelBulkEditor::buildBulkForm(
			$this->getModel(),
			$this->getKeys(),
			$this->getEditParametersClass(),
			$this->getBulkUpdateModelAction(),
			$this->provideFormDefaultSettings()
		);

		$this->shareBulkEditButtons();
		$this->loadBulkEditExtraViews();

		$this->manageBeforeBulkEdit();

		return $this->modelFormHelper->render();
	}

	public function manageBeforeBulkEdit()
	{

	}

	public function shareBulkEditButtons()
	{
		//		$this->getExtendedEditButtons();
		//
		//		if((isset($this->editButtons))&&(count($this->editButtons)))
		//			view()->share('buttons', $this->editButtons);
		//
		//		$this->addNavbarButton(
		//			$this->getBackToListButton()
		//		);
	}

	public function getKeys() : array
	{
		return $this->keys;
	}

	public function validateBulkKeys(Request $request)
	{
		$table = $this->getPlaceholderModel()->getTable();
		$key = $this->getPlaceholderModel()->getKeyName();

		$request->validate([
			'ids' => 'required|array',
			'ids.*' => 'exists:' . $table . ',' . $key
		]);
	}

	public function getUpdaterHelperClassName() : string
	{
		return CrudModelBulkUpdater::class;
	}

	public function bulkUpdate(Request $request)
	{
		$this->setKeys($request);

		$keyName = $this->getPlaceholderModel()->getKeyName();

		$models = $this->getModelClass()::whereIn(
			$keyName,
			$this->getKeys()
		)->get();

		foreach($models as $model)
			$this->_update($request, $model);

		return redirect()->route('iframe.close');
	}

	public function getRelationshipsManagerClass()
	{
		return null;
	}

	public function getBulkUpdateModelAction()
	{
		return $this->getRouteUrlByType('bulkUpdate');
	}
}