<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrudModelReplicatingUpdater extends CrudModelUpdater
{
	protected function replicateModel() : Model
	{
		return CrudModelClonerHelper::cloneIfClonable(
			$this->getModel()
		);
	}

	protected function setReplicatedModel(Model $model) : void
	{
		$this->setModel($model);
		$this->getFieldsetsProvider()->setModel($model);
	}

	public function bindRequest(Request $request) : Model
	{
		$this->setRequest($request);

		$parameters = $this->getValidatedRequestParameters();

		return DB::transaction(function() use($parameters)
		{
			$this->setReplicatedModel(
				$this->replicateModel()
			);

			return $this->bindParameters($parameters);
		});
	}
}
