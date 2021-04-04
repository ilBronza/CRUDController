<?php

namespace ilBronza\CRUD\Traits;

trait CRUDRoutingTrait
{
	public function getIndexUrl()
	{
		return $this->getRouteUrlByType('index');
	}

	public function getRouteNameByType(string $type)
	{
		$pieces = $this->getRouteBaseNamePieces();
		$pieces[] = $type;

		return implode(".", $pieces);		
	}

	public function getRouteParametersByType(string $type)
	{
		$parameters = $this->getRouteBaseParameters();

		if(in_array($type, ['update', 'delete', 'show', 'edit']))
			$parameters[] = $this->modelInstance;

		return $parameters;
	}

	public function getRouteUrlByType(string $type)
	{
		$actionString = $this->getRouteNameByType($type);

		$parameters = $this->getRouteParametersByType($type);

		//return ('contacts.create', []);
		//return ('contacts.edit', [$contact]);
		return route($actionString, $parameters);
	}

	public function getRouteBaseParameters()
	{
		return [];
	}

	public function getRouteBaseNamePieces()
	{
		return [
			$this->getLcfirstPluralModelClassname(new $this->modelClass())
		];
	}

}