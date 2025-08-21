<?php

namespace IlBronza\CRUD\Http\Controllers\Traits\StandardTraits;

use IlBronza\CRUD\Traits\CRUDDeleteTrait;

trait PackageStandardDestroyTrait
{
	use CRUDDeleteTrait;

	public $allowedMethods = ['destroy'];

	public function destroy($model)
	{
		$model = $this->findModel($model);

		return $this->_destroy($model);
	}
}