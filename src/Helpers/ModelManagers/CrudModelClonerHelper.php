<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\CRUD\Helpers\ModelManagers\Interfaces\ClonableModelInterface;
use Illuminate\Database\Eloquent\Model;

class CrudModelClonerHelper
{
	public Model $model;
	public Model $clonedModel;

	protected function setModel(Model $model)
	{
		$this->model = $model;
	}

	protected function getModel() : Model
	{
		return $this->model;
	}

	protected function getClonedModel() : Model
	{
		return $this->clonedModel;
	}

	protected function unsetNotClonableFields()
	{
		$notClonableFields = $this->getModel()->getNotClonableFields();

		foreach($notClonableFields as $field)
			$this->getClonedModel()->$field = null;

		$this->clonedModel->save();
	}

	protected function associateRelations()
	{
		$relations = $this->getModel()->getClonableRelations();

		foreach($relations as $relation)
		{
			$values = $this->getModel()->$relation()->get();

			CrudModelAssociatorHelper::associateRelation(
				$this->getClonedModel(),
				$relation,
				$values,
				$duplicateDirectRelations = true
			);
		}
	}

	public function _clone() : Model
	{
		$this->clonedModel = $this->getModel()->replicate();

		$this->unsetNotClonableFields();

		$this->associateRelations();

		return $this->getClonedModel();
	}

	static function clone(ClonableModelInterface $model) : Model
	{
		$helper = new static();

		$helper->setModel($model);

		return $helper->_clone();
	}

	static function cloneIfClonable(Model $model) : Model
	{
		if ($model instanceof ClonableModelInterface)
			return static::clone($model);

		$clonedModel = $model->replicate();
		$clonedModel->save();

		return $clonedModel;
	}
}