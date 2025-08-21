<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\Buttons\Button;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait CRUDRelationshipTrait
{
	public $disableRelationshipsManager;
	public $teaserMode;

	public function teaserMode(bool $value = true) : static
	{
		$this->disableRelationshipsManager();
		$this->teaserMode = $value;

		return $this;
	}

	public function isInTeaserMode() : bool
	{
		return !! $this->teaserMode;
	}

	public function disableRelationshipsManager(bool $value = true)
	{
		$this->disableRelationshipsManager = $value;
	}

	public function hasDisabledRelationshipsManager() : bool
	{
		return !! $this->disableRelationshipsManager;
	}

	public function filterRelationshipsFields(array $fields)
	{
		foreach($fields as $key => $field)
			if(empty($field['relation']))
				unset($fields[$key]);

		return $fields;
	}

	public function filterFieldsByForeignKeysExistence(array $fields) : array
	{
		$attributes = Schema::getColumnListing($this->modelInstance->getTable());

		foreach($fields as $key => $field)
		{
			if(in_array($key, $attributes))
				continue;

			unset($fields[$key]);
		}

		return $fields;
	}

	public function filterFieldsByForeignRelationshipsExistence(array $fields) : array
	{
		$attributes = Schema::getColumnListing($this->modelInstance->getTable());

		foreach($fields as $key => $field)
		{
			$relationName = $field['relation'];

			$relationType = $this->modelInstance->{$relationName}();

			if(class_basename($relationType) == 'BelongsTo')
				continue;

			unset($fields[$key]);
		}

		return $fields;
	}

	public function getForeignKeysFieldsByType(string $type = 'store') : array
	{
		$fields = $this->getFlattenFormFieldsByType($type);

		$relationshipFields = $this->filterRelationshipsFields($fields);

		return $this->filterFieldsByForeignKeysExistence($relationshipFields);		
	}

	public function getForeignRelationshipsFieldsByType(string $type = 'store') : array
	{
		$fields = $this->getFlattenFormFieldsByType($type);

		$relationshipFields = $this->filterRelationshipsFields($fields);

		return $this->filterFieldsByForeignRelationshipsExistence($relationshipFields);
	}

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

	private function relateHasOneElements(string $relationship, $related)
	{
		if(! $related)
			return ;

		$foreign = $this->modelInstance->{$relationship}()->getForeignKeyName();

		$this->modelInstance->{$foreign} = $related;
	}

	private function relateBelongsToManyElements(string $relationship, $related)
	{
		ddd("queste non le stiamo usando più 04 2024");
		$this->modelInstance->{$relationship}()->sync($related);
	}

	private function relateBelongsToElements(string $relationship, $related)
	{
		if((is_array($related))&&(count($related) == 0))
			$related = null;

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

		$relationshipsFields = $this->getUpdateFieldsetsProvider()
			->getRelationshipsFields();

		// $relationshipsFields = $this->getRelationshipsFieldsByType($type);

		foreach($relationshipsFields as $relationship => $fieldParameters)
		{
			if(! request()->has($relationship))
				continue;

			$values = request()->input($relationship, []);

			if($this->tryCustomMethod($relationship, $values) !== null)
				continue;

			$this->callStandardMethod($fieldParameters['relation'], $values);
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

		if($relationshipType == 'MorphToMany')
			return $this->getMorphToManyRelationshipButton($relationship);

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
		$baseModelClassname = Str::camel(
			class_basename(
				$this->getModelClass()
			)
		);

		return __('crud::crud.add', [
			'what' => __('crudModels.' . $baseModelClassname . Str::studly($relationship))]
		);		
	}

	public function getRelationshipButton(string $relationship)
	{
		if(! $href = $this->getRelationshipButtonUrl($relationship))
			return ;

		$text = $this->getCreateRelationshipsButtonLabel($relationship);
		$icon = $this->getCreateRelationshipsButtonIcon($relationship);

		return Button::create([
			'href' => $href,
			'text' => $text,
			'icon' => $icon
		]);
	}

	public function buildEditableRelationshipsButtons()
	{
		foreach($this->getEditableRelationships() as $relationship)
			if($button = $this->getRelationshipButton($relationship))
				$this->showButtons[] = $button;
	}
}