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

	public function getValidationArrayByType(string $type)
	{
		$fieldsets = $this->getFormFieldsetsByType($type);

		$validationArray = [];

		foreach($fieldsets as $fields)
			foreach($fields as $fieldName => $validation)
				if(count($validation) == 1)
					$validationArray[$fieldName] = array_pop($validation);
				else
					$validationArray[$fieldName] = $validation['rules'];

		return $validationArray;
	}
}