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

		$allowedMethods = $this->allowedMethods;

		if (method_exists($this, 'hasCreateEmptyButton') && $this->hasCreateEmptyButton())
			$allowedMethods[] = 'createEmpty';

		return array_values(array_unique($allowedMethods));
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
