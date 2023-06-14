<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Traits\CRUDDbFieldsTrait;
use IlBronza\Form\Facades\Form;
use IlBronza\Notifications\Notifications\SlackNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

trait CRUDValidateTrait
{
	use CRUDDbFieldsTrait;

	public function getRelationshipsFieldsByType(string $type)
	{
		$fieldsets = $this->getFormFieldsetsByType($type);

		$relationshipsFields = [];

		foreach($fieldsets as $fieldset)
		{
			if(! $this->userCanSeeFieldsetByRoles($fieldset))
				continue;

			$fields = $this->getFieldsetFields($fieldset);

			foreach($fields as $fieldName => $validation)
				if(isset($validation['relation']))
					$relationshipsFields[$fieldName] = $validation;
		}

		return $relationshipsFields;
	}

	private function cleanParametersFromRelationshipsByType(array $parameters, string $type)
	{
		$fieldsetProviderMethod = implode("", [
			'get',
			ucfirst($type),
			'FieldsetsProvider'
		]);

		$relationshipsFields = $this->{$fieldsetProviderMethod}()
			->getRelationshipsFields();

		// dd($relationshipsFields);
		// $relationshipsFields = $this->getRelationshipsFieldsByType($type);

		return array_diff_key(
					$parameters, 
					$relationshipsFields
				);
	}

	private function getParametersForRelationshipsByType(array $parameters, string $type)
	{
		// $relationshipsFields = $this->getRelationshipsFieldsByType($type);

		$relationshipsFields = $this->getUpdateFieldsetsProvider()
			->getRelationshipsFields();

		return array_intersect_key(
					$parameters, 
					$relationshipsFields
				);
	}

	public function convertFormToRequestType(string $type)
	{
		$types = [
			'updateEditor' => 'editor',
			'edit' => 'edit',
			'update' => 'edit',
			'create' => 'create',
			'store' => 'create'
		];

		return $types[$type];
	}

	public function getFormFieldsetsByType(string $type)
	{
		// if(config('crud.alertOldFieldsetMethods'))
		// 	Notification::route('slack', 'https://hooks.slack.com/services/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')
		// 		->notify(new SlackNotification('getFormFieldsetsByType ' . $type . ' da commentare commentata in favore del nuovo sistema. ' . request()->path()));

		// throw new \Exception('getFormFieldsetsByType commentata in favore del nuovo sistema');

		//edit or create or editUpdate
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

	private function getValidationArrayByFieldset(array $fieldset, array $validationArray)
	{
		if(! $this->userCanSeeFieldsetByRoles($fieldset))
			return $validationArray;

		$fields = $this->getFieldsetFields($fieldset);

		foreach($fields as $fieldName => $fieldContent)
			$validationArray = $this->addValidationArrayField($validationArray, $fieldContent, $fieldName);

		foreach($fieldset['fieldsets'] ?? [] as $fieldset)
			$validationArray = $this->getValidationArrayByFieldset($fieldset, $validationArray);

		return $validationArray;
	}

	public function getValidationArrayByType(string $type)
	{
		$fieldsets = $this->getFormFieldsetsByType($type);

		$validationArray = [];

		foreach($fieldsets as $fieldset)
			$validationArray = $this->getValidationArrayByFieldset($fieldset, $validationArray);

		return $validationArray;
	}

}