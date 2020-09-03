<?php

namespace ilBronza\CRUD\Traits;

trait CRUDBelongsToRoutingTrait
{
	public function getRouteBaseParameters()
	{
		return [$this->parentModel];
	}

	public function getRouteBaseNamePieces()
	{
		return [
			$this->getLcfirstPluralModelClassname($this->parentModel),
			$this->getLcfirstPluralModelClassname(new $this->modelClass())
		];
	}

}