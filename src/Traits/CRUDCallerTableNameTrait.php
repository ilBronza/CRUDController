<?php

namespace ilBronza\CRUD\Traits;

trait CRUDCallerTableNameTrait
{
	private function manageCallerTableName()
	{
		if(! $callerTableName = request()->input('callerTableName', null))
			return ;

		$this->callerTablename = $callerTableName;
		view()->share('callerTableName', $callerTableName);
	}
}