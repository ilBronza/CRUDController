<?php

namespace IlBronza\CRUD\Helpers\ModelManagers\Traits;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use Illuminate\Database\Eloquent\Model;

trait ModelManagersSettersAndGettersTraits
{
	public function setFieldsetsProvider()
	{
		$this->fieldsetsProvider = $this->initializeFieldsetsProvider();
	}

	public function getFieldsetsProvider() : FieldsetsProvider
	{
		if(! $this->fieldsetsProvider)
			$this->setFieldsetsProvider();

		return $this->fieldsetsProvider;
	}

	public function setFieldsetParametersFile(FieldsetParametersFile $parametersFile)
	{
		$this->parametersFile = $parametersFile;

		$this->parametersFile->setModelManager($this);
	}

	public function getFieldsetParametersFile() : FieldsetParametersFile
	{
		return $this->parametersFile;
	}

	public function setModel(Model $model)
	{
		$this->model = $model;

		view()->share('modelInstance', $model);
	}

	public function getModel() : Model
	{
		return $this->model;
	}
}