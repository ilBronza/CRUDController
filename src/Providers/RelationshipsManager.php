<?php

namespace IlBronza\CRUD\Providers;

use IlBronza\CRUD\Providers\RelationshipParameters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class RelationshipsManager
{
	public $name;
	public $type;
	public $model;
	public $relationships;

	abstract function getAllRelationsParameters();

	public function getModelClass()
	{
		return get_class($this->model);
	}

	public function getRelationsParameters()
	{
		$parameters = $this->getAllRelationsParameters();

		return $parameters[$this->type];
	}

	public function getRelationParameters(string $relation)
	{
		$parameters = $this->getAllRelationsParameters();

		return $parameters[$this->type]['relations'][$relation];
	}

	private function instantiateAllRelations()
	{
		$this->relationships = collect();

		$relationsParameters = $this->getRelationsParameters();

		foreach($relationsParameters['relations'] as $name => $parameters)
			$this->addRelationship($name, $parameters);

		$this->loadModelRelatedElements();
	}

	private function getRelationMethodByParameters(array $relationParameters) : string
	{
		return $relationParameters['relation'];
	}

	private function instantiateSingleRelation(string $relation) : self
	{
		$relationParameters = $this->getRelationParameters($relation);

		$this->relationship = $this->createRelationship($relation, $relationParameters);

		return $this;

		dd($relationship);

		$dummyRelatedModel = $this->model->{$relationMethod}()->make();
		$relationKeyName = $dummyRelatedModel->getKeyName();

		if(! is_array($modelKey))
			$modelKey = [$modelKey];

		$this->relatedModels = $this->model->{$relationMethod}()->whereIn($relationKeyName, $modelKey)->get();
	}

	public function __construct(string $type = 'show', Model $model, string $relation = null, $modelKey = null)
	{
		$this->name = $type;
		$this->type = $type;
		$this->model = $model;
		$this->modelKey = $modelKey;

		if(! $relation)
			return $this->instantiateAllRelations();

		return $this->instantiateSingleRelation($relation);
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

	public function getRelationships()
	{
		return $this->relationships;
	}

	public function getRelationshipsMethodsArray()
	{
		return $this->relationships->pluck('relation')->toArray();
	}

	public function manageAjaxTableRequest()
	{
		foreach($this->getRelationships() as $name => $relationshipsParameters)
			if($name == request()->model)
				return $relationshipsParameters->setShowParameters();
	}

	public function getCustomDom()
	{
		dd($this);
	}
}