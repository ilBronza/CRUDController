<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\Datatables\Datatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait CRUDArchiveTrait
{
	public function _archive(Request $request, Model $model)
	{
		$request->validate([
			'arvhive' => 'nullable|string'
		]);

/*		$model->archive($request->input('archive'));*/
		$model->archive();

		$updateParameters = [];
		$updateParameters['success'] = true;
		$updateParameters['action'] = 'removeRow';

		return $updateParameters;
	}

	public function getArchivedFieldsGroups()
	{
		return $this->archivedFieldsGroups;
	}

	public function archived(Request $request)
	{
		$tableName = 'archived' . $this->getTableName();
		$fieldsGroupsNames = $this->getArchivedFieldsGroups();

		$this->table = Datatables::create(
			$tableName,
			$this->getTableFieldsGroups($fieldsGroupsNames),
			function()
			{
				return $this->getArchivedElements();
			},
			false,
			[],
			$this->modelClass
		);

		$this->table->addBaseModelClass($this->modelClass);

		return $this->table->renderPage();
	}
}