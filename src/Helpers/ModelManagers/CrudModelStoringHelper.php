<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\CRUD\Helpers\ModelManagers\Traits\ModelManagersSettersAndGettersTraits;
use IlBronza\FormField\FormField;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\Ukn\Facades\Ukn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class CrudModelStoringHelper implements CrudModelManager
{
	use ModelManagersSettersAndGettersTraits;

	public $fieldsetsProvider;
	public $model;
	public Request $request;

	static function create(
		Model $model,
		FieldsetParametersFile $parametersFile,
		Request $request
	) : static
	{
		$helper = new static();

		$helper->setModel($model);
		$helper->setFieldsetParametersFile($parametersFile);

		return $helper;
	}

	static function saveByRequest(
		Model $model,
		FieldsetParametersFile $parametersFile,
		Request $request
	) : Model
	{
		Ukn::w('sostituire questo con la create sia qua che su storing qua sotto');
	// 	$helper = static::create($model, $parametersFile, $request);

		$helper = new static();

		$helper->setModel($model);
		$helper->setFieldsetParametersFile($parametersFile);

		return $helper->bindRequest($request);
	}

	public function sanitizeParametersAndValues(array $parameters) : array
	{
		$fields = $this->getFieldsetsProvider()->getAllFieldsArray();

		foreach($fields as $fieldName => $fieldParameters)
		{
			if(substr($fieldName, -13) == '_confirmation')
			{
				unset($parameters[$fieldName]);

				continue;
			}

			$formField = FormField::createFromArray($fieldParameters);

			//avoid repopulation of disabled fields or non compiled fields
			if(isset($parameters[$fieldName]))
				$parameters[$fieldName] = $formField->transformValueBeforeStore($parameters[$fieldName] ?? null);

			// //RESET NULL JSON FORM FIELDS
			// if($formField instanceof JsonFormField)
			// 	if(! isset($parameters[$fieldName]))
			// 		$parameters[$fieldName] = [];
		}

		return $parameters;
	}

	public function getValidationParameters() : array
	{
		return $this->getFieldsetsProvider()->getValidationParameters();
	}

	public function getValidatedRequestParameters() : array
	{
		$this->setFieldsetsProvider();

		$parameters = $this->getRequest()->validate(
			$this->getValidationParameters()
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
			if(array_key_exists($requestName, $parameters))
				$model->$attributeName = $parameters[$requestName];

		$model->save();

		$this->associateRelationshipsByType($parameters);

		return $model;
	}

	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

	public function getRequest() : Request
	{
		return $this->request ?? request();
	}

	public function bindRequest(Request $request) : Model
	{
		$this->setRequest($request);

		$parameters = $this->getValidatedRequestParameters();

		return $this->bindParameters($parameters);
	}

}