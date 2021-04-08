<?php

namespace IlBronza\CRUD\Traits;

use App\Providers\Helpers\dgButton;

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

	public function getCreateButton() : dgButton
	{
		$href = $this->getCreateButtonUrl();
		$text = $this->getCreateButtonText();

        return new dgButton($href, $text, 'plus');
	}
}