<?php

namespace IlBronza\CRUD\Traits;

trait CRUDBelongsToRoutingTrait
{
	public function getRouteBaseParameters()
	{
		return [$this->parentModel];
	}

	public function getRouteBaseNamePieces()
	{
		if(! empty($this->routeBaseNamePieces))
			return $this->routeBaseNamePieces;

		return [
			$this->getLcfirstPluralModelClassname($this->parentModel),
			$this->getLcfirstPluralModelClassname(new ($this->getModelClass()()))
		];
	}
}