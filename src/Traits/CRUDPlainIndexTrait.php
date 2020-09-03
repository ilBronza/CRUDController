<?php

namespace ilBronza\CRUD\Traits;

use \newdatatable;

trait CRUDPlainIndexTrait
{
	public function index()
	{
		return $this->_index();
	}
}