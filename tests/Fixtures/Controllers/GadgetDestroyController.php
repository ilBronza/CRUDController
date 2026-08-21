<?php

namespace IlBronza\CRUD\Tests\Fixtures\Controllers;

use IlBronza\CRUD\Traits\CRUDDeleteTrait;

class GadgetDestroyController extends GadgetCRUD
{
	use CRUDDeleteTrait;

	public $allowedMethods = ['destroy'];

	public function destroy($gadget)
	{
		$gadget = $this->findModel($gadget);

		return $this->_destroy($gadget);
	}
}
