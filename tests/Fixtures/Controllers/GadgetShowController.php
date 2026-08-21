<?php

namespace IlBronza\CRUD\Tests\Fixtures\Controllers;

use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;

class GadgetShowController extends GadgetCRUD
{
	use CRUDShowTrait;
	use CRUDRelationshipTrait;

	public $allowedMethods = ['show'];

	public $allowedShowDBFields = ['name', 'description'];

	public function show(string $gadget)
	{
		$gadget = $this->findModel($gadget);

		return $this->_show($gadget);
	}
}
