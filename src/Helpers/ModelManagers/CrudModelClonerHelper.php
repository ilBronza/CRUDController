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
	}

	protected function associateRelations()
	{
		$relations = $this->getModel()->getClonableRelations();

		foreach($relations as $relation)
		{
			$values = $this->getModel()->$relation()->get();

			CrudModelAssociatorHelper::associateRelation(
				$this->getModel(),
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

		dd('maranzo');

		return $this->getClonedModel();
	}

	static function clone(ClonableModelInterface $model) : Model
	{
		$helper = new static();

		$helper->setModel($model);
		$helper->_clone();

		return $this->getClonedModel();
	}
}