<?php

namespace ilBronza\CRUD\Traits;

trait CRUDRelationshipTrait
{
	private function getRelationTypeFieldsByFormType(string $formType)
	{
		$relations = [];

		$fieldsets = $this->getFormFieldsets($formType);

		foreach($fieldsets as $fields)
			foreach($fields as $field)
				if(isset($field['relation']))
					$relations[$field['relation']] = $field['multiple'] ?? false;

		return $relations;
	}

	private function getRelationTypeFieldsByFormTypes(array $formTypes)
	{
		$relations = [];

		foreach($formTypes as $formType)
			$relations = array_merge(
				$relations, 
				$this->getRelationTypeFieldsByFormType($formType)
			);

		return $relations;
	}

	public function learnMethodType($method)
	{
		return get_class($this->modelInstance->{$method}());
	}

	private function relateBelongsToManyElements(string $relationship, $related)
	{
		$this->modelInstance->{$relationship}()->sync($related);
	}

	private function relateBelongsToElements(string $relationship, $related)
	{
		$this->modelInstance->{$relationship}()->associate($related);
		$this->modelInstance->save();
	}

	private function relateMorphToManyElements(string $relationship, $related)
	{
		$this->modelInstance->{$relationship}()->sync($related);
	}

	private function tryCustomMethod(string $relationship, $values)
	{
		$customAssociationMethod = 'relate' . ucfirst($relationship);
		if(method_exists($this, $customAssociationMethod))
			return $this->$customAssociationMethod($relationship, $values);

		return null;
	}

	private function callStandardMethod($relationship, $values)
	{
		$relationshipsFields = $this->learnMethodType($relationship);
		$associationMethod = 'relate' . class_basename($relationshipsFields) . 'Elements';

		$this->$associationMethod($relationship, $values);
	}

	public function associateRelationshipsByType(array $parameters, string $type)
	{
		$parameters = $this->getParametersForRelationshipsByType($parameters, $type);

		foreach($parameters as $relationship => $values)
		{
			if($this->tryCustomMethod($relationship, $values) !== null)
				continue;

			$this->callStandardMethod($relationship, $values);
		}
	}	
}