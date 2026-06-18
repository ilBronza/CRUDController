<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Interfaces\CondensableModelInterface;
use IlBronza\CRUD\Traits\Model\CondensableModelTrait;
use IlBronza\Ukn\Ukn;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

use function redirect;
use function trans;
use function view;

trait CRUDBulkCondenseTrait
{
	protected array $keys = [];

	public function condense(Request $request)
	{
		$this->setCondenseKeys($request);

		return $this->_condense();
	}

	public function storeCondense(Request $request)
	{
		$this->setCondenseKeys($request);

		$request->validate([
			'master_id' => 'required|in:' . implode(',', $this->keys),
		]);

		$result = $this->condenseByModelAndIds(
			$request->input('master_id'),
			$this->keys
		);

		Ukn::s($result['message']);

		return redirect()->to(
			$this->getCondenseRedirectUrl()
		);
	}

	public function setCondenseKeys(Request $request) : void
	{
		$this->validateCondenseBulkKeys($request);

		$this->keys = $request->input('ids', []);
	}

	public function getCondenseKeys() : array
	{
		return $this->keys;
	}

	public function validateCondenseBulkKeys(Request $request) : void
	{
		$model = $this->getModelClass()::make();
		$table = $model->getTable();
		$key = $model->getKeyName();

		$request->validate([
			'ids' => 'required|array|min:2',
			'ids.*' => 'exists:' . $table . ',' . $key,
		]);
	}

	protected function _condense()
	{
		$models = $this->loadCondenseModels($this->getCondenseKeys());

		$this->assertModelsAreCondensable($models);

		view()->share('pageTitle', trans('crud::crud.condenseTitle'));

		return view('crud::utilities.condense.form', [
			'models' => $models,
			'ids' => $this->getCondenseKeys(),
			'storeCondenseUrl' => $this->getStoreCondenseUrl(),
			'backToListUrl' => $this->getCondenseRedirectUrl(),
			'relationships' => $this->getRelationshipsArray(),
		]);
	}

	protected function loadCondenseModels(array $ids) : Collection
	{
		$query = $this->getModelClass()::query()->whereIn(
			$this->getModelClass()::make()->getKeyName(),
			$ids
		);

		$relationships = $this->getRelationshipsArray();

		if(count($relationships))
			$query->withCount($relationships);

		return $query->get();
	}

	protected function assertModelsAreCondensable(Collection $models) : void
	{
		foreach($models as $model)
		{
			if(! $model instanceof CondensableModelInterface)
				throw new \Exception(
					class_basename($model) . ' must implement ' . CondensableModelInterface::class . ' to be condensable'
				);

			if(! in_array(CondensableModelTrait::class, class_uses_recursive($model)))
				throw new \Exception(
					class_basename($model) . ' must use ' . CondensableModelTrait::class . ' to be condensable'
				);
		}
	}

	public function getStoreCondenseUrl() : string
	{
		return $this->getModelClass()::make()->getStoreBulkCondenseUrl();
	}

	public function getCondenseRedirectUrl() : string
	{
		return $this->getModelClass()::make()->getIndexUrl();
	}
}
