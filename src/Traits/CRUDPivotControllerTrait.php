<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\Buttons\Button;
use IlBronza\Form\Facades\Form;

trait CRUDPivotControllerTrait
{
	public function getBackToListButton()
	{
		if($this->getModel()?->getParent()?->getShowUrl())
			return Button::create([
				'name' => 'back_to_list',
				'icon' => 'bars',
				'text' => 'crud::buttons.backToList',
				'href' => $this->getModel()?->getParent()?->getShowUrl()
			]);
	}
}