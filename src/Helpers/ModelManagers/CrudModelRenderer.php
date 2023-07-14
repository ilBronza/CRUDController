<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelFormHelper;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\ShowFieldsetsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

class CrudModelRenderer extends CrudModelFormHelper
{
	public $method = 'GET';

	static function buildRenderer(
		Model $model,
		FieldsetParametersFile $parametersFile,
		// string $action,
		// array $formOptions = []
	) : static
	{
		$helper = new static();

		$helper->setModel($model);
		$helper->setFieldsetParametersFile($parametersFile);
		$helper->setFieldsetsProvider();

		// if($helper->getModel()->exists)
		// 	$helper->loadRelationshipsValues();

		$helper->instantiateForm("", []);

		$helper->setFormFieldsets();

		return $helper;
	}

	public function initializeFieldsetsProvider() : FieldsetsProvider
	{
		return ShowFieldsetsProvider::setFieldsetsParametersByFile(
				$this->getFieldsetParametersFile(),
				$this->getModel()
			);
	}

	public function render() : View
	{
		return $this->_render();
	}

	public function _render()
	{
		$this->getFieldsetsProvider()->assignModelToFields();

		return view("crud::uikit.show", [
			'_showView' => 'crud::uikit._show',
			'modelInstance' => $this->getModel(),
			'canEditModelInstance' => true,
			'fieldsets' => $this->getFieldsetsProvider()->provideFieldsetsCollection()
		]);
	}

	// public function loadRelationshipsValues()
	// {
	// 	$extraTablerelatedFields = $this->getFieldsetsProvider()->getExtraTableRelationshipsFields();

	// 	foreach($extraTablerelatedFields as $relation => $fieldParameters)
    //     {
    //         $elements = $this->getModel()->{$fieldParameters['relation']}()->allRelatedIds()->toArray();

    //         $this->getModel()->{$relation} = $elements;
    //     }
	// }

	public function getCardClasses() : array
	{
		return config('form.editCardClasses');
	}

	public function getTitle() : string
	{
		return $this->getTranslationByKey('cardTitleShow', ['element' => $this->getModel()?->getName()]);
	}

	public function getIntro() : string
	{
		return $this->getTranslationByKey('cardIntroShow');
	}
}