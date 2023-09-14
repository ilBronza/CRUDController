<?php

namespace IlBronza\CRUD\Traits;

trait CRUDRoutingTrait
{
	public function getCreateUrl()
	{
		return $this->getRouteUrlByType('create');
	}

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
		if($model = $this->getModel())
			return $model->getIndexUrl();

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

	public function getRouteBaseNamePrefix() : ? string
	{
		return null;
	}

	public function getRouteBaseNamePiecesByModelClass()
	{
		return $this->getLcfirstPluralModelClassname(
			new ($this->getModelClass())()
		);
	}

	public function getRouteBaseNamePieces()
	{
		if(! empty($this->routeBaseNamePieces))
			return $this->routeBaseNamePieces;

		if($prefix = $this->getRouteBaseNamePrefix())
			return [
				$prefix . $this->getRouteBaseNamePiecesByModelClass()
			];

		return [
			$this->getRouteBaseNamePiecesByModelClass()
		];
	}

}