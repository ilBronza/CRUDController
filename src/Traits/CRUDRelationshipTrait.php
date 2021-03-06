<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Support\Str;

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

		$relationshipsFields = $this->getRelationshipsFieldsByType($type);

		foreach($relationshipsFields as $relationship => $fieldParameters)
		{
			$values = request()->input($relationship, []);

			if($this->tryCustomMethod($relationship, $values) !== null)
				continue;

			$this->callStandardMethod($relationship, $values);
		}
	}

	public function getEditableRelationships()
	{
		return $this->editableMethodRelationships ?? $this->showMethodRelationships ?? [];
	}

	public function getRelationshipButtonUrl(string $relationship)
	{
		$relationshipType = $this->getRelationshipType($relationship);

		if($relationshipType == 'HasMany')
			return $this->getHasManyRelationshipButton($relationship);

		if($relationshipType == 'BelongsTo')
			return $this->getBelongsToRelationshipButton($relationship);

		if($relationshipType == 'BelongsToMany')
			return $this->getBelongsToManyRelationshipButton($relationship);

		mori('relationship button type: ' . $relationshipType);
	}

	public function getCreateRelationshipsButtonIcon(string $relationship)
	{
		return 'plus';
	}

	public function getCreateRelationshipsButtonLabel(string $relationship)
	{
		return __('crud::crud.create', [
			'what' => __('crud::relationships.' . Str::camel($relationship))]
		);		
	}

	public function getRelationshipButton(string $relationship)
	{
		if(! $url = $this->getRelationshipButtonUrl($relationship))
			return ;

		$text = $this->getCreateRelationshipsButtonLabel($relationship);
		$icon = $this->getCreateRelationshipsButtonIcon($relationship);

		return new \dgButton($url, $text, $icon);
	}

	public function buildEditableRelationshipsButtons()
	{
		foreach($this->getEditableRelationships() as $relationship)

			if($button = $this->getRelationshipButton($relationship))
				$this->showButtons[] = $button;
	}
}