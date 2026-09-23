<?php

namespace IlBronza\CRUD\Traits;

trait CRUDRelationshipsManagerTrait
{
	public function setRelationshipsManager()
	{
		if(! $relationshipManagerClass = $this->getRelationshipsManagerClass())
			return false;

		if(empty($this->relationshipManager))
		{
			$this->relationshipManager = new ($relationshipManagerClass)($this->modelInstance);

			if ($this->relationshipManager->usesLegacyRelationsParameters())
				$this->relationshipManager->setLegacyRelationsType(
					$this->getRelationshipsManagerLegacyType()
				);
		}

		return $this->relationshipManager;

	}

	/**
	 * The action remains relevant only while a legacy manager is normalized.
	 * New managers receive no action/type parameter.
	 */
	public function getRelationshipsManagerLegacyType() : string
	{
		if (isset($this->relationshipsManagerLegacyType))
			return $this->relationshipsManagerLegacyType;

		if (method_exists($this, 'getAction'))
			return $this->getAction();

		return 'show';
	}

	/**
	 * This is controller state used only to read legacy parameter containers.
	 * It is deliberately never forwarded to managers using the new contract.
	 */
	public function setRelationshipsManagerLegacyType(string $type) : static
	{
		$this->relationshipsManagerLegacyType = $type;

		return $this;
	}

	public function useRelationshipsManager()
	{
		$this->setRelationshipsManager();

		if(! $this->relationshipManager)
			return false;

		if(request()->ajax())
			return $this->relationshipManager->manageAjaxTableRequest();

		$this->relationshipsTableNames = [];
		$this->relationshipsElements = [];

		foreach ($this->relationshipManager->getSections() as $section)
			$section->prepare();

		view()->share('relationshipManager', $this->relationshipManager);
	}

	public function useSingleRelationRelationshipsManager(string $relation, $modelKey)
	{
		$relationshipManagerClass = $this->getRelationshipsManagerClass();

		if (! $relationshipManagerClass)
			return false;

		$this->relationshipManager = new ($relationshipManagerClass)(
			$this->modelInstance,
			$relation,
			$modelKey
		);

		if ($this->relationshipManager->usesLegacyRelationsParameters())
			$this->relationshipManager->setLegacyRelationsType(
				$this->getRelationshipsManagerLegacyType()
			);

		$relationshipManager = $this->relationshipManager;
		$relationship = $this->relationshipManager->getRelationship();

		$relationship->elementsGetter = function() use($relationshipManager)
		{
			$relationMethod = $relationshipManager->relationship->relation;

			$dummyRelatedModel = $relationshipManager->model->{$relationMethod}()->make();
			$relationKeyName = $dummyRelatedModel->getKeyName();

			if(! is_array($relationshipManager->modelKey))
				$relationshipManager->modelKey = [$relationshipManager->modelKey];

			return $relationshipManager->model->{$relationMethod}()->whereIn($relationKeyName, $relationshipManager->modelKey)->get();
		};

		return $relationship->renderTableRowsArray();
	}
}
