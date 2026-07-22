<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelReplicatingUpdater;

trait CRUDUpdateByReplicationTrait
{
	use CRUDUpdateTrait;

	public function getUpdaterHelperClassName() : string
	{
		return CrudModelReplicatingUpdater::class;
	}
}
