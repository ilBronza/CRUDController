<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\Buttons\Button;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelEditor;
use IlBronza\Form\Helpers\FieldsetsProvider\EditFieldsetsProvider;
use Illuminate\Http\Request;

use function app;
use function config;
use function dd;
use function method_exists;
use function request;
use function view;
trait CRUDEditTrait
{
	use CRUDRelationshipsManagerTrait;

	//edit parameters
	public $editView;
	// public $standardEditView = 'form::uikit.form';
	public $standardEditView = 'crud::uikit.edit';

	/**
	 * get edit view name
	 *
	 * if declared an overridden view return it, otherwise return default one
	 *
	 * @return string
	 **/
	public function getEditView()
	{
		if($this->editView)
			return $this->editView;

		return $this->standardEditView;
	}

	/**
	 * get update model action form update form
	 *
	 * return [].update route with given model instance key
	 *
	 * @return string
	 **/
	public function getUpdateModelAction()
	{
		return $this->getRouteUrlByType('update');
	}

	/**
	 * share parameters to populate edit view
	 *
	 * @return callable
	 **/
	public function shareDefaultEditFormParameters()
	{
		return $this->shareDefaultFormParameters('edit');
	}

	public function loadEditRelationshipsValues()
	{
        foreach($this->relatedFields ?? [] as $relation => $fieldName)
        {
            $elements = $this->modelInstance->{$relation}()->get();

            $this->modelInstance->{$fieldName} = [];

            if(count($elements) == 0)
                continue;

            $this->modelInstance->{$fieldName} = $elements->pluck(
                $elements->first()->getKeyName()
            )->toArray();
        }
    }

	public function getExtendedEditButtons()
	{
	}

	public function getBackToListButton() : Button
	{
		return Button::create([
			'name' => 'back_to_list',
			'icon' => 'bars',
			'text' => 'crud::buttons.backToList',
			'href' => $this->getModel()->getIndexUrl()
		]);

	}

	public function shareEditButtons()
	{
		$this->getExtendedEditButtons();

		if((isset($this->editButtons))&&(count($this->editButtons)))
			view()->share('buttons', $this->editButtons);

		$this->addNavbarButton(
			$this->getBackToListButton()
		);
	}

	public function addEditExtraViews()
	{
		
	}

    public function loadEditExtraViews()
    {
    	$this->addEditExtraViews();

    	//DEPRECATED 06/2022 - use form extraViews
        // $this->shareExtraViews();
    }

    public function manageBeforeEdit() {
    }

	public function shareModels()
	{
		//TODO accorpare sta roba presente anche in show in un unico helper (creare show helper e edit helper e che sia finita)
		view()->share('modelInstance', $this->getModel());

		if(isset($this->parentModel))
			view()->share('parentModelInstance', $this->parentModel);
	}

	public function shareExtraParameters()
	{
		//TODO accorpare sta roba presente anche in show in un unico helper (creare show helper e edit helper e che sia finita)
		$this->shareModels();


		if(method_exists($this, 'getRelationshipsManagerClass'))
			if($this->getRelationshipsManagerClass())
				return $this->useRelationshipsManager();
	}

	/**
	 * get modelInstance edit form
	 *
	 * @return view
	 **/
	public function _edit($modelInstance)
	{
		$this->setModel(
			$modelInstance
		);

		$this->setPagetitle();
		$this->checkIfUserCanUpdate();

		if($this->requestHasRefreshRow())
			return $this->manageRelatedRefreshRow();

		/**
		 * creare qua un trait per gestire i parametri extra in generale
		 * e usare le extraViews positions in modo univoco dappertutto
		 */
		$editParameters = $this->shareExtraParameters();

		if(request()->ajax())
			return $editParameters;

		$this->manageReturnBack();

		$this->modelFormHelper = CrudModelEditor::buildForm(
			$this->getModel(),
			$this->getEditParametersClass(),
			$this->getUpdateModelAction(),
			$this->provideFormDefaultSettings()
		);

		$this->shareEditButtons();
		$this->loadEditExtraViews();

		$this->manageBeforeEdit();

		return $this->modelFormHelper->render();
	}
}