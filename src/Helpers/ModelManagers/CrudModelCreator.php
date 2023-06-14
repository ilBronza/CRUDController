<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelFormHelper;
use IlBronza\Form\Helpers\FieldsetsProvider\CreateFieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;

class CrudModelCreator extends CrudModelFormHelper
{
	public $method = 'POST';

	public function initializeFieldsetsProvider() : FieldsetsProvider
	{
		return CreateFieldsetsProvider::setFieldsetsParametersByFile(
				$this->getFieldsetParametersFile(),
				$this->getModel()
			);
	}

	public function getCardClasses() : array
	{
		return config('form.createCardClasses');
	}

	public function getTitle() : string
	{
		return $this->getTranslationByKey('cardTitleCreate');
	}

	public function getIntro() : string
	{
		return $this->getTranslationByKey(
			'cardIntroCreate',
			[
				'type' => $this->getModel()->getTranslatedClassname()
			]
		);
	}
}