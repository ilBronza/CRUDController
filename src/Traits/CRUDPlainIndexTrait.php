<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;

trait CRUDPlainIndexTrait
{
	public function index(Request $request)
	{
		return $this->_index($request);
	}
}