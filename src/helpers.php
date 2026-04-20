<?php

use IlBronza\CRUD\Helpers\CompulsoryConfigHelper;

if(! function_exists('cconfig'))
{
	function cconfig(string $key) : mixed
	{
		return CompulsoryConfigHelper::get($key);
	}
}
