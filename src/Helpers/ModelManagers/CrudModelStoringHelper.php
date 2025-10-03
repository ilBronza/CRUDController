<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use Carbon\Carbon;
use IlBronza\CRUD\Helpers\CrudRequestHelper;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelAssociatorHelper;
use IlBronza\CRUD\Helpers\ModelManagers\Traits\ModelManagersSettersAndGettersTraits;
use IlBronza\FormField\FormField;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\Ukn\Facades\Ukn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function array_key_exists;
use function class_uses_recursive;
use function dd;
use function get_class;
use function in_array;
use function method_exists;

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
		Request $request,
		array $events = [],
		callable $callback = null
	) : Model
	{
		$helper = static::create($model, $parametersFile, $request);

		// $helper = new static();

		// $helper->setModel($model);
		// $helper->setFieldsetParametersFile($parametersFile);

		$result = $helper->bindRequest($request);

		$helper->fireEvents($events);

		if($callback)
			$callback();

		if(CrudRequestHelper::isSaveAndCopy($request))
			return CrudModelClonerHelper::clone($result);

		return $result;
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

	public function validateParameters(array $validationParamters) : array
	{
		return $this->getRequest()->validate(
			$validationParamters
		);
	}

	public function getValidatedRequestParameters() : array
	{
		$this->setFieldsetsProvider();

		$validationParamters = $this->getValidationParameters();

		$parameters = $this->validateParameters($validationParamters);

		return $this->sanitizeParametersAndValues($parameters);
	}

	// public function getRelationType(string $relationshipName) : string
	// {
	// 	return class_basename($this->getModel()->{$relationshipName}());
	// }

	private function relateHasOneElements(string $relationshipMethod, $toRelate)
	{
		if(! $toRelate)
			return ;

		$foreign = $this->getModel()->{$relationshipMethod}()->getForeignKeyName();

		$this->getModel()->{$foreign} = $toRelate;
	}

	private function relateBelongsToManyElements(string $relationshipMethod, $toRelate)
	{
		if((is_string($toRelate))||(is_null($toRelate)))
			$toRelate = [$toRelate];

		$relation = $this->getModel()->{$relationshipMethod}();

		if($pivotClass = $relation->getPivotClass())
		{
			if(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($pivotClass)))
			{
				$alreadyRelated = $relation->withPivot(['id'])->get();

				$pivotToRemove = $alreadyRelated->map(function($item) use($toRelate)
				{
					if(! in_array($item->getKey(), $toRelate))
					{
						return $item->pivot->getKey();
					}
				});

				$pivotClass::whereIn($pivotClass::make()->getKeyName(), $pivotToRemove)->update(
					[
						'deleted_at' => Carbon::now()
					]
				);
			}
		}

		if((count($toRelate) == 1)&&($toRelate[0] == null))
			return $this->getModel()->{$relationshipMethod}()->sync([]);

		$this->getModel()->{$relationshipMethod}()->sync($toRelate);
	}

	private function relateBelongsToElements(string $relationshipMethod, $toRelate)
	{
		if((is_array($toRelate))&&(count($toRelate) == 0))
			$toRelate = null;

		$this->getModel()->{$relationshipMethod}()->associate($toRelate);
	}

	private function relateMorphToManyElements(string $relationshipMethod, $toRelate)
	{
		$this->getModel()->{$relationshipMethod}()->sync($toRelate);
	}

	private function getMorphManeElementsToRelate(string $relationshipMethod, $toRelate) : Collection
	{
		if((is_null($toRelate))||(count($toRelate) == 0))
			return collect();

		$placeholderModel = $this->getModel()->{$relationshipMethod}()->make();

		$keyName = $placeholderModel->getKeyName();

		return $placeholderModel->query()->whereIn($keyName, $toRelate)->get();
	}

	private function relateHasOneThroughElements(string $relationshipMethod, $toRelate)
	{
		$relationshipSetterMethod = 'set' . Str::studly($relationshipMethod);

		if(! method_exists($this->getModel(), $relationshipSetterMethod))
			throw new \Exception('dichiara ' . $relationshipSetterMethod . ' su ' . get_class($this->getModel()) . ' per salvare una relazione hasOneThrough');

		return $this->getModel()->$relationshipSetterMethod($toRelate);
	}

	private function relateMorphManyElements(string $relationshipMethod, $toRelate)
	{
		$relation = $this->getModel()->{$relationshipMethod}();

		$currentRelatedModels = $relation->get();

		$toRelatedModels = $this->getMorphManeElementsToRelate($relationshipMethod, $toRelate);

		if(count($toRelatedModels) > 0)
			$this->getModel()->{$relationshipMethod}()->saveMany($toRelatedModels);

		if(is_null($toRelate))
			$toRelate = [];

		$toRemoveRelatedModels = $currentRelatedModels->filter(function($value) use($toRelate)
		{
			return ! in_array($value->getKey(), $toRelate);
		});

		$typeField = $relation->getMorphType();
		$idField = $relation->getForeignKeyName();

		foreach($toRemoveRelatedModels as $toRemoveRelatedModel)
		{
			$toRemoveRelatedModel->$typeField = null;
			$toRemoveRelatedModel->$idField = null;

			$toRemoveRelatedModel->save();
		}
	}

	public function associateRelationshipsByType(array $parameters)
	{
		$extraTableRelationships = $this->getFieldsetsProvider()->getExtraTableRelationshipsFields();

		foreach($extraTableRelationships as $relationshipField)
		{
			if(! array_key_exists($relationshipField['name'], $parameters))
				continue;

			$values = $parameters[$relationshipField['name']];

			$customAssociationMethodName = 'relate' . ucfirst($relationshipField['relation']);

			if(method_exists($this->getModel(), $customAssociationMethodName))
			{
				$this->getModel()->$customAssociationMethodName($values);

				continue;
			}

			// $relationType = $this->getRelationType(
			// 	$relationshipField['relation']
			// );

			$relationType = CrudModelAssociatorHelper::getRelationTypeName(
					$this->getModel(),
					$relationshipField['relation']
				);

			$standardAssociationMethod = 'relate' . $relationType . 'Elements';

			$this->$standardAssociationMethod($relationshipField['relation'], $values);

			$customEventMethodName = 'relation' . ucfirst($relationshipField['relation']) . 'Set';

			if(method_exists($this->getModel(), $customEventMethodName))
				$this->getModel()->$customEventMethodName($values);
		}
	}

	public function modelUsesExtrafields() : bool
	{
		return in_array(
			'IlBronza\CRUD\Traits\Model\CRUDModelExtraFieldsTrait',
			class_uses_recursive(
				$this->getModel()
			)
		);
	}

	public function mustBeSetAfterStoring(string $attributeName) : bool
	{
		if(($model = $this->getModel())->exists)
			return false;

		if(! $this->modelUsesExtraFields())
			return false;

		$extraFields = $model->getExtraFieldsCasts();

		return array_key_exists($attributeName, $extraFields);
	}

	public function bindParameter($requestName, $attributeName, $parameters)
	{
		if(array_key_exists($requestName, $parameters))
		{
			$setterName = 'set' . Str::studly($attributeName);

			if(method_exists($this->getModel(), $setterName))
				$this->getModel()->{$setterName}($parameters[$requestName]);

			else
			{
				// Log::critical('dichiara ' . $setterName . ' su ' . get_class($this->getModel()));
				$this->getModel()->$attributeName = $parameters[$requestName];
			}
		}
	}

	public function bindParameters(array $parameters) : Model
	{
		$bindableFieldsNames = $this->getFieldsetsProvider()->getBindableAttributeFieldsNames();

		$model = $this->getModel();

		foreach($bindableFieldsNames as $requestName => $attributeName)
		{
			if($this->mustBeSetAfterStoring($attributeName))
				continue;

			$this->bindParameter($requestName, $attributeName, $parameters);
			unset($bindableFieldsNames[$requestName]);
		}

		if(! $model->exists)
			$model->save();


		foreach($bindableFieldsNames as $requestName => $attributeName)
			$this->bindParameter($requestName, $attributeName, $parameters);

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

	public function fireEvents(array $events)
	{
		foreach($events as $event)
			$event::dispatch($this->getModel());
	}

}