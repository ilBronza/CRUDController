<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDReorderableStandardTrait
{
	public function getStoreMassReorderUrl(array $data = []) : string
	{
		return $this->getKeyedRoute('storeMassReorder', $data, false);
	}

	public function getSortingIndexField() : string
	{
		return 'sorting_index';
	}

	public function setSortingIndex(int $value, bool $save = true) : void
	{
		$field = $this->getSortingIndexField();

		$this->$field = $value;

		if($save)
			$this->save();
	}
}