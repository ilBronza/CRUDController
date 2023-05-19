<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\Datatables\Datatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait CRUDArchiveTrait
{
	public function getArchivedElements() : Collection
	{
		return $this->getModelClass()::archived()->get();
	}

	public function _archive(Request $request, Model $model)
	{
		$request->validate([
			'archive' => 'nullable|string'
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
			$this->getModelClass()
		);

		$this->table->addBaseModelClass($this->getModelClass());

		return $this->table->renderPage();
	}
}