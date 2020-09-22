<?php

namespace ilBronza\CRUD\Traits;

use ilBronza\CRUD\Traits\CRUDDbFieldsTrait;
use ilBronza\Form\Facades\Form;

trait CRUDValidateTrait
{
	use CRUDDbFieldsTrait;

	public function getRelationshipsFieldsByType(string $type)
	{
		$fieldsets = $this->getFormFieldsetsByType($type);

		$relationshipsFields = [];

		foreach($fieldsets as $fieldset)
			foreach($fieldset as $fieldName => $validation)
				if(isset($validation['relation']))
					$relationshipsFields[$fieldName] = $validation;

		return $relationshipsFields;
	}

	private function cleanParametersFromRelationshipsByType(array $parameters, string $type)
	{
		$relationshipsFields = $this->getRelationshipsFieldsByType($type);

		return array_diff_key(
					$parameters, 
					$relationshipsFields
				);
	}

	private function getParametersForRelationshipsByType(array $parameters, string $type)
	{
		$relationshipsFields = $this->getRelationshipsFieldsByType($type);

		return array_intersect_key(
					$parameters, 
					$relationshipsFields
				);
	}

	public function convertFormToRequestType(string $type)
	{
		$types = [
			'update' => 'edit',
			'store' => 'create'
		];

		return $types[$type];
	}

	private function getFormFieldsetsByType(string $type)
	{
		//edit or create
		$formType = $this->convertFormToRequestType($type);

		if(! property_exists($this, 'formFields'))
			return $this->getValidationArrayByTypeFromDBByType($formType);

		return $this->getFormFieldsets($formType);
	}

	private function addValidationArraySingleRow(array $validationArray, array $fieldContent, string $fieldName) :array
	{
		$validationArray[$fieldName] = array_pop($fieldContent);

		return $validationArray;
	}

	public function addJsonFieldValidationArrayField(array $validationArray, array $fieldContent, string $fieldName) :array
	{
		$validationArray[$fieldName] = $fieldContent['rules'];

		foreach($fieldContent['fields'] as $subFieldName => $subFieldContent)
		{
			$_validationKey = $fieldName . '.' . $subFieldName;

			$validationArray[$_validationKey] = 'array';
			$validationArray = $this->addValidationArrayField($validationArray, $subFieldContent, $_validationKey . '.*');
		}

		return $validationArray;
	}

	private function addValidationArrayMultipleRow(array $validationArray, array $fieldContent, string $fieldName) :array
	{
		if($fieldContent['type'] == 'json')
			return $this->addJsonFieldValidationArrayField($validationArray, $fieldContent, $fieldName);

		$validationArray[$fieldName] = $fieldContent['rules'];

		return $validationArray;
	}

	private function addValidationArrayField(array $validationArray, array $fieldContent, string $fieldName)
	{
		if(count($fieldContent) == 1)
			return $this->addValidationArraySingleRow($validationArray, $fieldContent, $fieldName);

		return $this->addValidationArrayMultipleRow($validationArray, $fieldContent, $fieldName);
	}

	public function getValidationArrayByType(string $type)
	{
		$fieldsets = $this->getFormFieldsetsByType($type);

		$validationArray = [];

		foreach($fieldsets as $fields)
			foreach($fields as $fieldName => $fieldContent)
				$validationArray = $this->addValidationArrayField($validationArray, $fieldContent, $fieldName);

		return $validationArray;
	}
}