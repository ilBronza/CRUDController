<?php

namespace IlBronza\CRUD\Http\Controllers\Traits;

use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;

use function view;

trait ControllerLogoTrait
{
	public function returnLogoImage()
	{
		$url = $this->modelInstance->getLogoImageUrl();

		return view('crud::utilities.logo._logo', [
			'modelInstance' => $this->modelInstance,
			'image' => $url
		]);
	}
}