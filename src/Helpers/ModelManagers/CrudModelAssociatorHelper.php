<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function get_class;

class CrudModelAssociatorHelper
{
	public Model $model;
	public string $relation;

	protected function setModel(Model $model)
	{
		$this->model = $model;
	}

	protected function getModel() : Model
	{
		return $this->model;
	}

	protected function setRelation(string $relation)
	{
		$this->relation = $relation;
	}

	protected function getRelation() : string
	{
		return $this->relation;
	}

	protected function setValues(array|Collection $values)
	{
		$this->values = $values;
	}

	protected function getValues() : array|Collection
	{
		return $this->values;
	}

	static function getRelationTypeName(Model $model, string $relation) : string
	{
		return class_basename(
			$model->{$relation}()
		);		
	}

	protected function _getRelationTypeName() : string
	{
		return static::getRelationTypeName(
			$this->getModel(),
			$this->getRelation()
		);
	}

	protected function sendAssociationEvent() : Model
	{
		$customEventMethodName = $this->getRelation() . 'Related';

		if(method_exists($this->getModel(), $customEventMethodName))
			$this->getModel()->$customEventMethodName(
				$this->getValues()
			);

		return $this->getModel();
	}

	protected function processAssociation(bool $duplicateDirectRelations = false)
	{
		$relationTypeName = $this->_getRelationTypeName();

		if($relationTypeName == 'HasMany')
		{
			foreach($this->getValues() as $value)
			{
				if($duplicateDirectRelations)
					$value = CrudModelClonerHelper::cloneIfClonable($value);

				$this->getModel()
				     ->{$this->getRelation()}()
				     ->save(
					     $value
				     );
			}

			return $this->sendAssociationEvent();
		}

		if($relationTypeName == 'HasOne')
		{
			foreach($this->getValues() as $value)
			{
				if($duplicateDirectRelations)
					$value = CrudModelClonerHelper::cloneIfClonable($value);

				$this->getModel()
				     ->{$this->getRelation()}()
				     ->save(
					     $value
				     );
			}

			return $this->sendAssociationEvent();
		}

		if($relationTypeName == 'BelongsToMany')
		{
			$this->getModel()
			     ->{$this->getRelation()}()
			     ->sync(
				     $this->getValues()
			     );

			return $this->sendAssociationEvent();
		}

		if($relationTypeName == 'MorphMany')
		{
			foreach($this->getValues() as $value)
				$this->getModel()
				     ->{$this->getRelation()}()
				     ->save(
					     CrudModelClonerHelper::cloneIfClonable($value)
				     );

			return $this->sendAssociationEvent();
		}

		throw new \Exception($relationTypeName . ' Errore manca questo tipo da associare in ' . get_class($this) . ' per la relazione ' . get_class($this->getModel()) . ' -> ' . $this->getRelation());
	}

	static function associateRelation(Model $model, string $relation, array|Collection $values, bool $duplicateDirectRelations = false)
	{
		if(! $values)
			return $model;

		$helper = new static();

		$helper->setModel($model);
		$helper->setRelation($relation);
		$helper->setValues($values);

		$helper->processAssociation($duplicateDirectRelations);

	}

}