<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\History;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Helpers\HistoryHelpers\HistoryFinderHelper;
use IlBronza\CRUD\Helpers\ModelHelpers\ModelFinderHelper;

class HistoryByFieldController extends CRUD
{
	public $allowedMethods = ['show'];

	public function show(string $model, string $key, string $field)
	{
		$model = ModelFinderHelper::getByClassKey($model, $key);

		return HistoryFinderHelper::getHistoryViewByModelFieldName($model, $field)->render();
	}
}