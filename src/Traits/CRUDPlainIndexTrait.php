<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;

trait CRUDPlainIndexTrait
{
	public function index(Request $request)
	{
		return $this->_index($request);
	}
}