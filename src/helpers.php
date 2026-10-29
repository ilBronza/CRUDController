<?php

use IlBronza\CRUD\Helpers\CompulsoryConfigHelper;
use Illuminate\Support\Facades\Auth;

if(! function_exists('throwIfAuthIdIsOne'))
{
	function throwIfAuthIdIsOne(string $message) : void
	{
		if(Auth::id() == 1)
			throw new \Exception($message);
	}
}

if(! function_exists('aERR'))
{
	function aERR(string $message) : void
	{
		throwIfAuthIdIsOne($message);
	}
}

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
