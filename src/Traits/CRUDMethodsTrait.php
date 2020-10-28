<?php

namespace ilBronza\CRUD\Traits;

trait CRUDMethodsTrait
{
	public function getAllowedMethods()
	{
		if(! $this->allowedMethods)
			throw new \Exception('public $allowedMethods non dichiarato nella classe estesa ' . get_class($this));

		return $this->allowedMethods;
	}

	public function methodIsAllowed(string $method)
	{
		return in_array($method, $this->getAllowedMethods());
	}	
}