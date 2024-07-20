<?php

namespace IlBronza\CRUD\Helpers\ModelHelpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ModelSchemaHelper
{
	static function getModelDatabaseFieldsByClass(string $fullQualifiedClassname)
	{
		$model = $fullQualifiedClassname::make();

		return static::getModelDatabaseFieldsByModel($model);

	}

	static function getModelDatabaseFieldsByModel(Model $model)
	{
		$table = $model->getTable();

		return static::getModelDatabaseFieldsByTable($table);

	}

	static function getModelDatabaseFieldsByTable(string $table)
	{
		return Schema::getColumnListing($table);
	}
}