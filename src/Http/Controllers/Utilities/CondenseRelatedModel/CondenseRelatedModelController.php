<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\CondenseRelatedModel;

use IlBronza\CRUD\CRUD;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class CondenseRelatedModelController extends CRUD
{
	public int $counter = 0;
	public int $brothersCounter = 0;

	abstract protected function getRelationshipsArray() : array;

	protected function addBrotherhoodQueryRules(Builder $query) : Builder
	{
		throw new \Exception(
			'Implement addBrotherhoodQueryRules() to use condenseByModel(), or use condenseByModelAndIds() instead.'
		);
	}

	protected function findCondenseModel(string $modelId) : Model
	{
		return $this->getModelClass()::findOrFail($modelId);
	}

	protected function getCondenseMasterModel() : Model
	{
		$model = $this->getModel();

		if(! $model instanceof Model)
			throw new \Exception('Condense master model not set');

		return $model;
	}

	protected function addRelationships(Builder $query) : Builder
	{
		return $query->with($this->getRelationshipsArray());
	}

	protected function findBrothers() : Collection
	{
		$query = $this->getModelClass()::query();
		$query = $this->addBrotherhoodQueryRules($query);
		$query = $this->addRelationships($query);

		return $query->get();
	}

	protected function associateRelated(string $relation, Model $related) : void
	{
		$this->getCondenseMasterModel()->$relation()->save($related);

		$this->counter ++;
	}

	protected function deleteBrother(Model $brother) : void
	{
		$brother->delete();
	}

	protected function condenseModel(Model $brother) : void
	{
		foreach($this->getRelationshipsArray() as $relation)
			foreach($brother->$relation as $related)
				$this->associateRelated($relation, $related);

		$this->brothersCounter ++;

		$this->deleteBrother($brother);
	}

	public function condenseByModel(string $modelId) : array
	{
		$this->setModel($this->findCondenseModel($modelId));

		$brothers = $this->findBrothers();

		foreach($brothers as $brother)
			$this->condenseModel($brother);

		return [
			'success' => true,
			'message' => $this->counter . ' elementi condensati per ' . $this->brothersCounter . ' elementi replicati e ' . count($this->getRelationshipsArray()) . ' relazioni differenti',
		];
	}

	public function condenseByModelAndIds(string $masterId, array $sourceIds) : array
	{
		return DB::transaction(function () use ($masterId, $sourceIds)
		{
			$this->counter = 0;
			$this->brothersCounter = 0;

			$this->setModel($this->findCondenseModel($masterId));

			$keyName = $this->getCondenseMasterModel()->getKeyName();

			$brothers = $this->getModelClass()::query()
				->whereIn($keyName, $sourceIds)
				->where($keyName, '!=', $masterId)
				->with($this->getRelationshipsArray())
				->get();

			foreach($brothers as $brother)
				$this->condenseModel($brother);

			return [
				'success' => true,
				'message' => trans('crud::crud.condenseSuccess', [
					'moved' => $this->counter,
					'condensed' => $this->brothersCounter,
				]),
			];
		});
	}
}
