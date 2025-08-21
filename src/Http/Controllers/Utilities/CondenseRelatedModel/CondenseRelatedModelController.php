<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\CondenseRelatedModel;


use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class CondenseRelatedModelController extends Controller
{
	protected Model $model;

	public int $counter = 0;
	public int $brothersCounter = 0;

	abstract protected function getModelClass() : string;
	abstract protected function getRelationshipsArray() : array;
	abstract protected function addBrotherhoodQueryRules(Builder $query) : Builder;

	protected function findModel(string $modelId) : Model
	{
		return $this->getModelClass()::findOrFail($modelId);
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

	protected function getModel() : Model
	{
		return $this->model;
	}

	protected function associateRelated(string $relation, Model $related)
	{
		$this->getModel()->$relation()->save($related);

		$this->counter ++;
	}

	protected function deleteBrother(Model $brother)
	{
		$brother->delete();
	}

	protected function condenseModel(Model $brother)
	{
		foreach($this->getRelationshipsArray() as $relation)
			foreach($brother->$relation as $related)
				$this->associateRelated($relation, $related);

		$this->brothersCounter ++;

		$this->deleteBrother($brother);
	}

	public function condenseByModel(string $modelId)
	{
		$this->model = $this->findModel($modelId);

		$brothers = $this->findBrothers();

		foreach($brothers as $brother)
			$this->condenseModel($brother);

		return [
			'success' => true,
			'message' => $this->counter . ' elementi condensati per ' . $this->brothersCounter . ' elementi replicati e ' . count($this->getRelationshipsArray()) . ' relazioni differenti'
		];
	}

	
}