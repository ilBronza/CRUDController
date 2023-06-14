<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use Illuminate\Database\Eloquent\Model;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;

interface CrudModelManager
{
	public function initializeFieldsetsProvider() : FieldsetsProvider;

	public function setModel(Model $model);
	public function getModel() : Model;
}