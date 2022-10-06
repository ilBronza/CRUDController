<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\Buttons\Button;

trait CRUDBelongsToButtonsTrait
{
	public function getCreateButtonUrl()
	{
		return $this->getRouteUrlByType('create');
	}

	public function getCreateButtonText()
	{
		//quotations
		$fileName = $this->getLcfirstPluralModelClassname($this->parentModel);

		//createQuantityFor
		$key = '.create' . class_basename($this->modelClass) . 'For__element';

		//element => elementName
		$element = $this->parentModel->getName();

		return trans(implode(".", [$fileName, $key]), compact('element'));
	}

	public function getCreateButton() : Button
	{
		return Button::create([
                'href' => $this->getCreateButtonUrl(),
                'translatedText' => $this->getCreateButtonText(),
                'icon' => 'plus'
            ]);
	}
}