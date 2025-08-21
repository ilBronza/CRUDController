<?php

namespace IlBronza\CRUD\Providers\RelationshipsManager;

trait RelationshipParametersGettersTrait
{
	public function isPolimorphicParentingRelationship() : bool
	{
		return $this->getRelationType() == 'MorphMany';
	}

	public function isParentingRelationship() : bool
	{
		return $this->getRelationType() == 'HasMany';
	}

	public function isMultipleRelationship() : bool
	{
		$relationType = $this->getRelationType();

		return $this->getRenderAsByRelationType($relationType) == 'table';
	}
}