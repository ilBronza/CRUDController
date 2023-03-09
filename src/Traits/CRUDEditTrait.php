<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelEditor;
use IlBronza\Form\Helpers\FieldsetsProvider\EditFieldsetsProvider;
use Illuminate\Http\Request;

trait CRUDEditTrait
{

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

	public function getExtendedCreateButtons()
	{

	}

	public function shareEditButtons()
	{
		$this->getExtendedEditButtons();

		if((isset($this->editButtons))&&(count($this->editButtons)))
			view()->share('buttons', $this->editButtons);
	}

	public function shareCreateButtons()
	{
		$this->getExtendedCreateButtons();

		if((isset($this->createButtons))&&(count($this->createButtons)))
			view()->share('buttons', $this->createButtons);
	}

	public function addEditExtraViews()
	{
		
	}

	public function addCreateExtraViews()
	{
		
	}

	public function loadCreateExtraViews()
	{
    	$this->addCreateExtraViews();

    	//DEPRECATED 06/2022 - use form extraViews
        // $this->shareExtraViews();		
	}

    public function loadEditExtraViews()
    {
    	$this->addEditExtraViews();

    	//DEPRECATED 06/2022 - use form extraViews
        // $this->shareExtraViews();
    }

    public function manageBeforeEdit() {
		$this->shareEditButtons();
		$this->loadEditExtraViews();
    }

	/**
	 * get modelInstance edit form
	 *
	 * @return view
	 **/
	public function _edit($modelInstance)
	{
		$this->setModel($modelInstance);

		$this->checkIfUserCanUpdate();
		$this->manageReturnBack();

		$this->modelFormHelper = CrudModelEditor::buildForm(
			$this->getModel(),
			$this->getEditParametersClass(),
			$this->getUpdateModelAction(),
			$this->provideFormDefaultSettings()
		);

		$this->manageBeforeEdit();

		return $this->modelFormHelper->render();
	}
}