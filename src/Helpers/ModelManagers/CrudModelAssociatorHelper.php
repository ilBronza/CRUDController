<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelClonerHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CrudModelAssociatorHelper
{
	public Model $model;

	protected function setModel(Model $model)
	{
		$this->model = $model;
	}

	protected function getModel() : Model
	{
		return $this->model;
	}

	static function getRelationTypeName(Model $model, string $relation) : string
	{
		return class_basename(
			$model->{$relation}()
		);		
	}

	protected function _getRelationTypeName(string $relation) : string
	{
		return static::getRelationTypeName(
			$this->getModel(),
			$relation
		);
	}

	static function associateRelation(Model $model, string $relation, array|Collection $values, bool $duplicateDirectRelations = false)
	{
		if(! $values)
			return $model;

		$helper = new static();

		$helper->setModel($model);

		$relationTypeName = $helper->_getRelationTypeName($relation);

		if($relationTypeName == 'HasMany')
		{
			foreach($values as $value)
			{
				if($duplicateDirectRelations)
					$value = CrudModelClonerHelper::clone($value);

				$this->getModel()
					->{$relation}()
					->save(
						$value
					);
			}

			return $this->getModel();
		}

		dd($relationType);

		$this->$customAssociationMethod($relationshipField['relation'], $values);

		$customEventMethodName = 'relation' . ucfirst($relationshipField['relation']) . 'Set';

		if(method_exists($this->getModel(), $customEventMethodName))
			$this->getModel()->$customEventMethodName($values);

		dd($relationTypeName);
	}

}