<?php

namespace IlBronza\CRUD\Tests\Fixtures\Controllers;

use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use Illuminate\Http\Request;

class GadgetEditUpdateController extends GadgetCRUD
{
	use CRUDEditUpdateTrait;

	public $allowedMethods = ['edit', 'update'];

	public $allowedEditDBFields = ['name', 'description'];

	public $allowedUpdateDBFields = ['name', 'description'];

	public function edit(string $gadget)
	{
		$gadget = $this->findModel($gadget);

		return $this->_edit($gadget);
	}

	public function update(Request $request, $gadget)
	{
		$gadget = $this->findModel($gadget);

		return $this->_update($request, $gadget);
	}
}
