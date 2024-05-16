<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelFormHelper;
use IlBronza\Form\Helpers\FieldsetsProvider\EditFieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;

class CrudModelEditor extends CrudModelFormHelper
{
	public $method = 'PUT';

	public function initializeFieldsetsProvider() : FieldsetsProvider
	{
		return EditFieldsetsProvider::setFieldsetsParametersByFile(
				$this->getFieldsetParametersFile(),
				$this->getModel()
			);
	}

	public function loadRelationshipsValues()
	{
		$extraTablerelatedFields = $this->getFieldsetsProvider()->getExtraTableRelationshipsFields();

		foreach($extraTablerelatedFields as $relation => $fieldParameters)
		{
			// $elements = $this->getModel()->{$fieldParameters['relation']}()->allRelatedIds()->toArray();

			$key = $this->getModel()->{$fieldParameters['relation']}()->make()->getKeyName();
			$table = $this->getModel()->{$fieldParameters['relation']}()->make()->getTable();

			$elements = $this->getModel()->{$fieldParameters['relation']}()->select($table . '.' . $key)->pluck($key)->toArray();

			$this->getModel()->{$relation} = $elements;
		}
	}

	public function getCardClasses() : array
	{
		return config('form.editCardClasses');
	}

	public function getTitle() : string
	{
		return $this->getTranslationByKey('cardTitleEdit', ['element' => $this->getModel()?->getName()]);
	}

	public function getIntro() : string
	{
		return $this->getTranslationByKey('cardIntroEdit');
	}
}