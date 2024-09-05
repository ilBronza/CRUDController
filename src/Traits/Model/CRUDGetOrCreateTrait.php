<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDGetOrCreateTrait
{
	static function getOrCreateByName(string $name) : static
	{
		if ($byname = static::getProjectClassName()::getByName($name))
			return $byname;

		$model = static::getProjectClassName()::make();
		$model->name = $name;

		$model->save();

		return $model;
	}
}