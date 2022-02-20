<?php

namespace IlBronza\CRUD\Traits;

trait CRUDRoutingTrait
{
	public function isSaveAndNew()
	{
		return request()->has('save_and_new');
	}

	public function isSaveAndRefresh()
	{
		return request()->has('save_and_refresh');
	}

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

	public function getRouteUrlByType(string $type, array $parameters = [])
	{
		$actionString = $this->getRouteNameByType($type);

		$parameters = array_merge(
			$parameters,
			$this->getRouteParametersByType($type)
		);

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