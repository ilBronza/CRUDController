<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\CondenseRelatedModel;

use IlBronza\CRUD\Traits\CRUDBulkCondenseTrait;

abstract class BulkCondenseController extends CondenseRelatedModelController
{
	use CRUDBulkCondenseTrait;

	public $allowedMethods = [
		'condense',
		'storeCondense',
	];
}
