<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use \newdatatable;

trait CRUDPlainIndexTrait
{
	public function index(Request $request)
	{
		return $this->_index($request);
	}
}