<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\CRUD\Helpers\ModelManagers\Traits\ModelManagersSettersAndGettersTraits;
use IlBronza\Form\Form;
use IlBronza\Form\Helpers\FieldsetsProvider\CreateFieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

abstract class CrudModelFormHelper
{
	use ModelManagersSettersAndGettersTraits;

	public $model;

	abstract function getCardClasses() : array;

	static function buildForm(
		Model $model,
		FieldsetParametersFile $parametersFile,
		string $action,
		array $formOptions = []
	) : static
	{
		$helper = new static();

		$helper->setModel($model);
		$helper->setFieldsetParametersFile($parametersFile);
		$helper->setFieldsetsProvider();

		if($helper->getModel()->exists)
			$helper->loadRelationshipsValues();

		$helper->instantiateForm($action, $formOptions);

		$helper->setFormFieldsets();

		return $helper;
	}

	public function getForm() : Form
	{
		return $this->form;
	}

	public function setFormFieldsets() : Form
	{
		// CreateFieldsetsProvider::addFieldsetsToFormByParametersFile(
		// 	$this->getForm(),
		// 	$this->getFieldsetParametersFile(),
		// 	$this->getModel()
		// );

		$this->getFieldsetsProvider()->setFieldsetsCollectionToForm();

		return $this->getForm();
	}

	public function render() : View
	{
		return $this->getForm()->render();
	}

	public function getFormMethod() : string
	{
		return $this->method;
	}

	public function getAction() : string
	{
		return $this->action;
	}

	public function getTranslationByKey(string $key, array $parameters = []) : string
	{
		$translationFileName = $this->getModel()->getTranslationsFileName();

		if(trans()->has($string = $translationFileName . '.' . $key))
			return trans($string, $parameters);

		return trans('crud::crud.' . $key, $parameters);
	}

	public function instantiateForm(string $action, array $formOptions) : Form
	{
		$this->form = Form::createFromArray([
			'action' => $action,
			'method' => $this->getFormMethod()
		]);

		$this->form->setDivider(
			$formOptions['divider'] ?? config('form.divider')
		);

		$this->form->hasCard(
			$formOptions['hasCard'] ?? config('form.hasCard')
		);

		$this->form->addCardClasses(
			$formOptions['cardClasses'] ?? $this->getCardClasses()
		);

		$this->form->setTitle(
			$formOptions['title'] ?? $this->getTitle()
		);

		if($formOptions['backToListUrl'] ?? false)
			$this->form->setBackToListUrl(
				$formOptions['backToListUrl']
			);

		if($formOptions['submitButtonText'] ?? false)
			$this->form->setSubmitButtonText(
				$formOptions['submitButtonText']
			);

		if($formOptions['saveAndNew'] ?? false)
			$this->form->addSaveAndNewButton();

		if($formOptions['saveAndRefresh'] ?? false)
			$this->form->addSaveAndRefreshButton();

		$this->form->setIntro(
			$this->getIntro()
		);

		$this->form->setModel(
			$this->getModel()
		);

		$this->getFieldsetsProvider()->setForm($this->form);

		return $this->form;
	}

}