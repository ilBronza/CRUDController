<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\FormField\FormField;

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

	public function getFieldParametersByTypeAndName(string $type, string $fieldName)
	{
		$fieldsets = $this->getFlattenFormFieldsByType($type);

		return $fieldsets[$fieldName];
	}

	public function getFormFieldByTypeAndName(string $type, string $fieldName)
	{
		$fieldParameters = $this->getFieldParametersByTypeAndName($type, $fieldName);
		$fieldParameters['name'] = $fieldName;

		return FormField::createFromArray($fieldParameters);
	}

}