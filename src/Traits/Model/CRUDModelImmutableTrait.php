<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelReplicatingUpdater;

trait CRUDModelImmutableTrait
{
	public function getImmutableUpdaterHelperClassName() : string
	{
		return CrudModelReplicatingUpdater::class;
	}
}
