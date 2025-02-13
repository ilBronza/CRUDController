<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelRenderer;
use IlBronza\CRUD\Traits\CRUDRelationshipsManagerTrait;
use IlBronza\CRUD\Traits\CRUDShowRelationshipsTrait;
use IlBronza\Form\Helpers\FieldsetsProvider\ShowFieldsetsProvider;
use Illuminate\Http\Request;

use function is_null;
use function view;

trait CRUDShowTrait
{
	use CRUDShowRelationshipsTrait;
	use CRUDRelationshipsManagerTrait;

	//edit parameters
	public $standardShowView = 'crud::uikit.show';
	public $showButtons = [];
	public ? bool $showEditLink = null;

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


	/**
	 * get show view name
	 *
	 * if declared an overridden view return it, otherwise return default one
	 *
	 * @return string
	 **/
	public function get_ShowView() : ? string
	{
		return $this->_showView ?? 'crud::uikit._show';
	}


	private function checkIfUserCanSee()
	{
		if(! $this->modelInstance->userCanSee(auth()->user()))
			abort(403);
	}

	private function getGuardedShowDBFields()
	{
		return $this->guardedShowDBFields ?? [];
	}

	//TODO mettere i campi visibili per show
	public function shareAllowedFields()
	{
		$allowedFields = $fields = array_keys($this->modelInstance->getAttributes());

		$allowedFields = array_diff($allowedFields, $this->getGuardedShowDBFields());
		view()->share('allowedFields', $allowedFields);
	}

	public function shareShowModels()
	{
		view()->share('modelInstance', $this->modelInstance);

		if(isset($this->parentModel))
			view()->share('parentModelInstance', $this->parentModel);
	}

	public function getEditModelIsntanceUrl()
	{
		return null;
	}


	public function showEditLink() : bool
	{
		if(! is_null($this->showEditLink))
			return $this->showEditLink;

		return config('crud.showEditLink', true);
	}

	private function shareShowParameters()
	{
		$this->shareShowModels();

		$relationships = $this->shareRelationships();

		if(request()->ajax())
			return $relationships;

		$this->shareAllowedFields();

		view()->share('showStickyButtonsNavbar', $this->showStickyButtonsNavbar);
		view()->share('canEditModelInstance', $this->canEditModelInstance);
		view()->share('editModelInstanceUrl', $this->getEditModelIsntanceUrl());

		view()->share('showEditLink', $this->showEditLink());

		if((in_array('index', $this->allowedMethods))&&(! $this->avoidBackToList))
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
		$this->getExtendedShowButtons();

		$this->setShowButtons();

		if(count($this->showButtons))
			view()->share('showButtons', $this->showButtons);
	}

	private function manageEditorRequest(Request $request)
	{
		$pluralModelType = $request->modelName;
		// $pluralModelType = 'quantities';

		$modelId = $request->rowId;

		return $this->useSingleRelationRelationshipsManager('show', $pluralModelType, $modelId);
	}

	public function getShowFieldsets()
	{
		if($file = $this->getShowParametersClass())
			return ShowFieldsetsProvider::getFieldsetsCollectionByParametersFile(
				$file,
				$this->modelInstance
			);
	}

	public function manageBeforeShow()
	{

	}

	public function _oldShow($modelInstance)
	{
		$this->modelInstance = $modelInstance;
		$view = $this->getShowView();
		$_showView = $this->get_ShowView();

		$showParameters = $this->shareShowParameters();

		if(request()->ajax())
			return $showParameters;


		$this->shareExtraViews();

		return view($view, [
			'_showView' => $_showView,
			'fieldsets' => $this->getShowFieldsets()
		]);

	}

	public function _show($modelInstance)
	{
		if(! $modelInstance)
			abort(404);

		if(! $this->getShowParametersClass())
			return $this->_oldShow($modelInstance);

		$this->setModel($modelInstance);

		$this->setPagetitle();

		$this->checkIfUserCanSee();

		if(request()->ibeditor)
			return $this->manageEditorRequest(request());

		$showParameters = $this->shareShowParameters();

		if(request()->ajax())
			return $showParameters;

		$this->shareShowButtons();

		$this->modelFormHelper = CrudModelRenderer::buildRenderer(
			$this->getModel(),
			$this->getShowParametersClass(),
			null, // $this->getUpdateModelAction(),
			null //$this->provideFormDefaultSettings()
		);

		$this->manageBeforeShow();

		if($this->isInTeaserMode())
			return $this->modelFormHelper->renderTeaser();

		return $this->modelFormHelper->render();
	}
}