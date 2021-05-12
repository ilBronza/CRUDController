<?php

namespace IlBronza\CRUD\Traits;

trait CRUDCallerTableNameTrait
{
	private function managecallertablename()
	{
		if(! $callertablename = request()->input('callertablename', null))
			return ;

		$this->callertablename = $callertablename;
		view()->share('callertablename', $callertablename);
	}
}