<?php

namespace IlBronza\CRUD\Traits;

trait CRUDFormFieldsTrait
{
	public function getAllRecursiveFieldsByFieldset(array $fieldset) : array
	{
		$fields = $this->getAllFieldsetFields($fieldset);

		foreach($fieldset['fieldsets'] ?? [] as $childFieldset)
			$fields = array_merge(
				$fields,
				$this->getAllRecursiveFieldsByFieldset($childFieldset)
			);

		return $fields;
	}

	public function getAllFlattenFormFieldsByType(string $type = 'store') : array
	{
		$fieldsets = $this->getFormFieldsetsByType($type);

		$result = [];

		foreach($fieldsets as $fieldset)
			$result = array_merge(
				$result,
				$this->getAllRecursiveFieldsByFieldset($fieldset)
			);

		return $result;
	}

	public function getFlattenFormFieldsByType(string $type = 'store') : array
	{
		$fields = $this->getAllFlattenFormFieldsByType($type);

		return $this->filterByRolesAndPermissions($fields);
	}
}