<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\CRUD\Helpers\ModelManagers\Traits\ModelManagersSettersAndGettersTraits;
use IlBronza\FormField\FormField;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class CrudModelStoringHelper
{
	use ModelManagersSettersAndGettersTraits;

	public $model;

	static function saveByRequest(
		Model $model,
		FieldsetParametersFile $parametersFile,
		Request $request
	) : Model
	{
		$helper = new static();

		$helper->setModel($model);
		$helper->setFieldsetParametersFile($parametersFile);

		return $helper->bindRequest($request);
	}

	public function sanitizeParametersAndValues(array $parameters) : array
	{
		$fields = $this->getFieldsetsProvider()->getAllFieldsArray();

		$confirmations = [];

		foreach($fields as $fieldName => $fieldParameters)
		{
			if(isset($fieldParameters['rules']['confirmed']))
				$confirmations[$fieldParameters['name'] . '_confirmation'] = true;

			$formField = FormField::createFromArray($fieldParameters);

			// if(isset($parameters[$fieldName]))
			$parameters[$fieldName] = $formField->transformValueBeforeStore($parameters[$fieldName] ?? null);

			// //RESET NULL JSON FORM FIELDS
			// if($formField instanceof JsonFormField)
			// 	if(! isset($parameters[$fieldName]))
			// 		$parameters[$fieldName] = [];
		}

		return array_diff_key($parameters, $confirmations);
	}

	public function getValidatedRequestParameters() : array
	{
		$this->setFieldsetsProvider();

		$parameters = $this->request->validate(
			$this->getFieldsetsProvider()->getValidationParameters()
		);

		return $this->sanitizeParametersAndValues($parameters);
	}

	public function getRelationType(string $relationshipName) : string
	{
		return class_basename($this->getModel()->{$relationshipName}());
	}

	private function relateHasOneElements(string $relationshipMethod, $related)
	{
		if(! $related)
			return ;

		$foreign = $this->getModel()->{$relationshipMethod}()->getForeignKeyName();

		$this->getModel()->{$foreign} = $related;
	}

	private function relateBelongsToManyElements(string $relationshipMethod, $related)
	{
		$this->getModel()->{$relationshipMethod}()->sync($related);
	}

	private function relateBelongsToElements(string $relationshipMethod, $related)
	{
		if((is_array($related))&&(count($related) == 0))
			$related = null;

		$this->getModel()->{$relationshipMethod}()->associate($related);
	}

	private function relateMorphToManyElements(string $relationshipMethod, $related)
	{
		$this->getModel()->{$relationshipMethod}()->sync($related);
	}

	public function associateRelationshipsByType(array $parameters)
	{
		$extraTableRelationships = $this->getFieldsetsProvider()->getExtraTableRelationshipsFields();

		foreach($extraTableRelationships as $relationshipField)
		{
			if(! array_key_exists($relationshipField['name'], $parameters))
				continue;

			$values = $parameters[$relationshipField['name']];

			$relationType = $this->getRelationType(
				$relationshipField['relation']
			);

			$customAssociationMethod = 'relate' . $relationType . 'Elements';

			$this->$customAssociationMethod($relationshipField['relation'], $values);
		}
	}

	public function bindParameters(array $parameters) : Model
	{
		$bindableFieldsNames = $this->getFieldsetsProvider()->getBindableAttributeFieldsNames();

		$model = $this->getModel();

		foreach($bindableFieldsNames as $requestName => $attributeName)
			$model->$attributeName = $parameters[$requestName];

		$model->save();

		$this->associateRelationshipsByType($parameters);

		return $model;
	}

	public function bindRequest(Request $request) : Model
	{
		$this->request = $request;

		$parameters = $this->getValidatedRequestParameters();

		return $this->bindParameters($parameters);
	}

}