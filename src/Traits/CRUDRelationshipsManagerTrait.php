<?php

namespace IlBronza\CRUD\Traits;

trait CRUDRelationshipsManagerTrait
{
	public function setRelationshipsManager(string $type = 'show')
	{
		if(! isset($this->relationshipsManagerClass))
			return false;

		if(empty($this->relationshipManager))
			$this->relationshipManager = new $this->relationshipsManagerClass($type, $this->modelInstance);

		return $this->relationshipManager;

	}

	public function useRelationshipsManager(string $type = 'show')
	{
		$this->setRelationshipsManager($type);

		if(! $this->relationshipManager)
			return false;

		$this->relationshipsTableNames = [];
		$this->relationshipsElements = [];

		foreach($this->relationshipManager->getRelationships() as $name => $relationshipsParameters)
			$relationshipsParameters->setShowParameters();

		view()->share('relationshipManager', $this->relationshipManager);
	}

	public function useSingleRelationRelationshipsManager(string $type = 'show', string $relation, $modelKey)
	{
		$this->relationshipManager = new $this->relationshipsManagerClass($type, $this->modelInstance, $relation, $modelKey);

		$relationshipManager = $this->relationshipManager;

		$this->relationshipManager->relationship->elementsGetter = function() use($relationshipManager)
		{
			$relationMethod = $relationshipManager->relationship->relation;

			$dummyRelatedModel = $relationshipManager->model->{$relationMethod}()->make();
			$relationKeyName = $dummyRelatedModel->getKeyName();

			if(! is_array($relationshipManager->modelKey))
				$relationshipManager->modelKey = [$relationshipManager->modelKey];

			return $relationshipManager->model->{$relationMethod}()->whereIn($relationKeyName, $relationshipManager->modelKey)->get();
		};

		return $this->relationshipManager->relationship->renderTableRowsArray();
	}
}