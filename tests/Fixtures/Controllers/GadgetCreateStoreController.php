<?php

namespace IlBronza\CRUD\Tests\Fixtures\Controllers;

use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;

class GadgetCreateStoreController extends GadgetCRUD
{
	use CRUDCreateStoreTrait;

	public $allowedMethods = ['create', 'store'];

	public $allowedCreateDBFields = ['name', 'description'];

	public $allowedStoreDBFields = ['name', 'description'];
}
