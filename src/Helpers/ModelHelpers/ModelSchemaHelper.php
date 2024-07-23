<?php

namespace IlBronza\CRUD\Helpers\ModelHelpers;

use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

	static function getFieldColumnByModel(Model $model, string $field) : Column
	{
		$table = $model->getTable();

		return static::getFieldColumnByTable($table, $field);
	}

	static function getFieldColumnByTable(string $table, string $field) : Column
	{
		return Schema::getConnection()->getDoctrineColumn($table, $field);
	}
}