<?php

namespace IlBronza\CRUD\Providers;

use Illuminate\Database\Eloquent\Model;

abstract class RelationshipsManager
{
	public $name;
	public $model;
	public $relationships;

	abstract function getAllRelationsParameters();

	public function getModelClass()
	{
		return get_class($this->model);
	}

	public function getRelationsParameters(string $type = 'show')
	{
		$parameters = $this->getAllRelationsParameters();

		return $parameters[$type];
	}

	public function __construct(string $type = 'show', Model $model)
	{
		$this->name = $type;
		$this->model = $model;

		$this->relationships = collect();

		$relationsParameters = $this->getRelationsParameters($type);

		foreach($relationsParameters['relations'] as $name => $parameters)
			$this->addRelationship($name, $parameters);

		$this->loadModelRelatedElements();
	}

	private function loadModelRelatedElements()
	{
		$relationshipsNames = $this->getRelationshipsMethodsArray();

		$this->model->loadMissing($relationshipsNames);
	}

	public function getModel()
	{
		return $this->model;
	}

	public function createRelationship(string $name, $parameters) : RelationshipParameters
	{
		if(is_string($parameters))
			return $this->createRelationship($name, [
				'controller' => $parameters
			]);

		return new RelationshipParameters($name, $parameters, $this);
	}

	public function addRelationship(string $name, $parameters)
	{
		$relationship = $this->createRelationship($name, $parameters);

		$this->relationships->put($name, $relationship);
	}

	public function getRelationshipsNamesArray()
	{
		return array_keys($this->relationships);
	}

	public function getReationships()
	{
		return $this->relationships;
	}

	public function getRelationshipsMethodsArray()
	{
		return $this->relationships->pluck('relation')->toArray();
	}

}