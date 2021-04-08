<?php

namespace IlBronza\CRUD\Traits;

trait CRUDMethodsTrait
{
	/**
	 * get allowedMethods array from extended model
	 *
	 * @return array
	 *
	 **/
	public function getAllowedMethods() : array
	{
		if(! $this->allowedMethods)
			throw new \Exception('public $allowedMethods non dichiarato nella classe estesa ' . get_class($this));

		return $this->allowedMethods;
	}

	/**
	 * check if given method is allowed in extended model
	 *
	 * @param string $method
	 *
	 * @return boolean
	 **/
	public function methodIsAllowed(string $method) : bool
	{
		return in_array($method, $this->getAllowedMethods());
	}	
}