<?php

namespace IlBronza\CRUD\Tests\Fixtures\Controllers;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Tests\Fixtures\Models\Gadget;

class GadgetCRUD extends CRUD
{
	public $modelClass = Gadget::class;
}
