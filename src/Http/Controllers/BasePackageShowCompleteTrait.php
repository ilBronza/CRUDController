<?php

namespace IlBronza\CRUD\Http\Controllers;

use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;

trait BasePackageShowCompleteTrait
{
    use BasePackageShowTrait;

	public function show(string $model)
	{
		$model = $this->findModel($model);

		return $this->_show($model);
	}
}