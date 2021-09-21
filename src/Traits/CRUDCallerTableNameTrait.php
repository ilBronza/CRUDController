<?php

namespace IlBronza\CRUD\Traits;

trait CRUDCallerTableNameTrait
{
	private function manageCallerTableName()
	{
		if(! $callertablename = request()->input('callertablename', null))
			return ;

		$this->callertablename = $callertablename;
		view()->share('callertablename', $callertablename);
	}
}