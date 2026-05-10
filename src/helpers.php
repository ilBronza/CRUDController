<?php

use IlBronza\CRUD\Helpers\CompulsoryConfigHelper;

if(! function_exists('cconfig'))
{
	function cconfig(string $key) : mixed
	{
		return CompulsoryConfigHelper::get($key);
	}
}

	/**
	 * 
	 * [
	 * 	"superadmin" => true,
	 * 	"administrator" => false
	 * ]
	 * 
	 * become
	 * 
	 * ['superadmin']
	 * 
	 **/

if(! function_exists('configKeys'))
{
	function configKeys(string $key) : mixed
	{
		return normalizeRoles(
			CompulsoryConfigHelper::get($key)
		);
	}
}

if(! function_exists('normalizeRoles'))
{
	
	function normalizeRoles(array $roles)
	{
		$result = [];

		foreach($roles as $key => $validity)
			if($validity)
				$result[] = $key;

		return $result;		
	}
}