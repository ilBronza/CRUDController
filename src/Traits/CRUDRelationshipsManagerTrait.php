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
}