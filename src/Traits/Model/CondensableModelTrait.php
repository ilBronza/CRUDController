<?php

namespace IlBronza\CRUD\Traits\Model;

trait CondensableModelTrait
{
	public function getBulkCondenseUrl(array $data = [])
	{
		return $this->getKeyedRoute('condense', $data, false);
	}

	public function getStoreBulkCondenseUrl(array $data = [])
	{
		return $this->getKeyedRoute('storeCondense', $data, false);
	}
}
