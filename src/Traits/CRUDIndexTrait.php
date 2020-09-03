<?php

namespace ilBronza\CRUD\Traits;

use \newdatatable;

trait CRUDIndexTrait
{
	// index parameters
	public $indexFieldsGroups = ['index'];
	public $indexCacheKey;

	public function _index()
	{
		$table = new newdatatable(request(), $this->getModelClassBasename(), $this->getIndexFieldsGroups(), $this->getIndexCacheKey(), function()
		{
			return $this->getIndexElements();
		});

		$table->addButton($this->getCreateButton());

		return $table->renderTable();
	}

	public function getIndexFieldsGroups()
	{
		return $this->indexFieldsGroups;
	}

	public function getIndexCacheKey()
	{
		return $this->indexCacheKey;
	}
}