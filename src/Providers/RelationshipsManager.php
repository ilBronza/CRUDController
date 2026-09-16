<?php

namespace IlBronza\CRUD\Providers;

use IlBronza\CRUD\Providers\RelationshipParameters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use LogicException;

abstract class RelationshipsManager
{
	public $name;
	public $type;
	public $model;
	public $relationships;
	public $modelKey;
	public $relationship;
	public ?string $legacyRelationsType = null;

	private ?string $requestedRelation = null;
	private bool $relationshipsInstantiated = false;
	public $displayType = 'Switcher';

	public function getModelClass()
	{
		return get_class($this->model);
	}

	/**
	 * New managers override this method with a direct relation map.
	 */
	public function getRelationsParameters() : array
	{
		$parameters = $this->getAllRelationsParameters();

		return $this->normalizeLegacyRelationsParameters($parameters);
	}

	/** Legacy extension point retained while old managers are migrated. */
	public function getAllRelationsParameters() : array
	{
		throw new LogicException(sprintf(
			'%s must implement getRelationsParameters(). getAllRelationsParameters() is supported only for legacy managers.',
			static::class
		));
	}

	public function usesLegacyRelationsParameters() : bool
	{
		return (new \ReflectionMethod($this, 'getRelationsParameters'))->getDeclaringClass()->getName() === self::class;
	}

	public function setLegacyRelationsType(string $type) : self
	{
		if ($this->relationshipsInstantiated)
			throw new LogicException('The legacy relationships type must be set before reading relationships.');

		$this->legacyRelationsType = $type;
		$this->name = $type;
		$this->type = $type;

		return $this;
	}

	public function getRelationParameters(string $relation) : array|string
	{
		$parameters = $this->getRelationsParameters();

		if (! array_key_exists($relation, $parameters))
			throw new LogicException(sprintf('Relationship "%s" is not declared by %s.', $relation, static::class));

		return $parameters[$relation];
	}

	private function instantiateAllRelations() : void
	{
		$this->relationships = collect();

		$relationsParameters = $this->getRelationsParameters();

		foreach($relationsParameters as $name => $parameters)
			$this->addRelationship($name, $parameters);

		$this->loadModelRelatedElements();
	}

	private function getRelationMethodByParameters(array $relationParameters) : string
	{
		return $relationParameters['relation'];
	}

	private function instantiateSingleRelation(string $relation) : void
	{
		$relationParameters = $this->getRelationParameters($relation);

		$this->relationship = $this->createRelationship($relation, $relationParameters);

	}

	/**
	 * New signature: __construct(Model $model, ?string $relation = null, mixed $modelKey = null).
	 * The old type-first signature is retained temporarily for legacy callers.
	 */
	public function __construct(Model|string $modelOrLegacyType, Model|string|null $modelOrRelation = null, mixed $relationOrModelKey = null, mixed $legacyModelKey = null)
	{
		if (is_string($modelOrLegacyType))
		{
			if (! $modelOrRelation instanceof Model)
				throw new InvalidArgumentException('The legacy RelationshipsManager constructor requires a model as its second argument.');

			$this->model = $modelOrRelation;
			$this->requestedRelation = $relationOrModelKey;
			$this->modelKey = $legacyModelKey;
			$this->setLegacyRelationsType($modelOrLegacyType);
		}
		else
		{
			if (! is_null($modelOrRelation) && ! is_string($modelOrRelation))
				throw new InvalidArgumentException('The new RelationshipsManager constructor accepts a relation name as its second argument.');

			$this->model = $modelOrLegacyType;
			$this->requestedRelation = $modelOrRelation;
			$this->modelKey = $relationOrModelKey;
		}
	}

	private function ensureRelationshipsInstantiated() : void
	{
		if ($this->relationshipsInstantiated)
			return;

		if(! $this->requestedRelation)
			$this->instantiateAllRelations();
		else
			$this->instantiateSingleRelation($this->requestedRelation);

		$this->relationshipsInstantiated = true;
	}

	private function loadModelRelatedElements()
	{
		$relationshipsNames = $this->relationships->pluck('relation')->toArray();

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
		return array_keys($this->getRelationships()->all());
	}

	public function getRelationships()
	{
		$this->ensureRelationshipsInstantiated();

		return $this->relationships;
	}

	public function getRelationship() : RelationshipParameters
	{
		$this->ensureRelationshipsInstantiated();

		return $this->relationship;
	}

	public function getRelationshipsMethodsArray()
	{
		return $this->getRelationships()->pluck('relation')->toArray();
	}

	public function manageAjaxTableRequest()
	{
		foreach($this->getRelationships() as $name => $relationshipsParameters)
			if($name == request()->model)
				return $relationshipsParameters->setShowParameters();
	}

	private function normalizeLegacyRelationsParameters(array $parameters) : array
	{
		// TODO: rimuovere il supporto al formato legacy
		// ['show'|'edit' => ['relations' => [...]]]
		// dopo la migrazione di tutti i RelationshipsManager.
		if (! $this->legacyRelationsType)
			throw new LogicException(sprintf(
				'Legacy RelationshipsManager %s requires an action type before its relations can be read.',
				static::class
			));

		if (! array_key_exists($this->legacyRelationsType, $parameters))
			throw new LogicException(sprintf(
				'Legacy RelationshipsManager %s does not declare the requested "%s" type.',
				static::class,
				$this->legacyRelationsType
			));

		$typeParameters = $parameters[$this->legacyRelationsType];

		if (! is_array($typeParameters) || ! array_key_exists('relations', $typeParameters))
			throw new LogicException(sprintf(
				'Legacy RelationshipsManager %s must declare ["%s" => ["relations" => [...]]].',
				static::class,
				$this->legacyRelationsType
			));

		if (! is_array($typeParameters['relations']))
			throw new LogicException(sprintf(
				'Legacy RelationshipsManager %s must declare "%s.relations" as an array.',
				static::class,
				$this->legacyRelationsType
			));

		return $typeParameters['relations'];
	}

	public function getCustomDom()
	{
		dd("get custom dom");
	}

	public function getDisplayType()
	{
		/*
			Possibili valori:
				- Flat
				- Switcher
				- Toggler
		*/
		return $this->displayType ?? config('crud.relationships.displayType', 'Flat');
	}

}
