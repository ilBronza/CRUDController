<?php

namespace IlBronza\CRUD\Helpers;

use Illuminate\Http\Request;

class CrudRequestHelper
{
	static function isSaveAndCopy(Request $request) : bool
	{
		return !! $request->input('save_and_copy', false);
	}
}