<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

trait CRUDFileParametersTrait
{
	private function getGenericParametersFile() : ? string
	{
		if($this->parametersFile ?? null)
			return $this->parametersFile;

		return null;
	}

	private function getParametersFileByType(string $type) : ? string
	{
		$propertyName = "{$type}ParametersFile";

		if($this->$propertyName ?? null)
			return $this->$propertyName;

		return $this->getGenericParametersFile();
	}

	public function getShowParametersClass() : ? FieldsetParametersFile
	{
		if($file = $this->getParametersFileByType('show'))
			return new $file();

		return null;
	}

	public function getCreateParametersClass() : ? FieldsetParametersFile
	{
		if($file = $this->getParametersFileByType('create'))
			return new $file();

		$parameters = $this->getFormFieldsetsByType('create');

		return FieldsetParametersFile::makeByParameters($parameters);
	}

	public function getStoreParametersClass() : FieldsetParametersFile
	{
		if($file = $this->getParametersFileByType('store'))
			return new $file();

		if($file = $this->getParametersFileByType('create'))
			return new $file();

		$parameters = $this->getFormFieldsetsByType('store');

		return FieldsetParametersFile::makeByParameters($parameters);
	}

	public function getEditParametersClass() : ? FieldsetParametersFile
	{
		if($file = $this->getParametersFileByType('edit'))
			return new $file();

		$parameters = $this->getFormFieldsetsByType('edit');

		return FieldsetParametersFile::makeByParameters($parameters);
	}

	public function getUpdateParametersClass() : ? FieldsetParametersFile
	{
		if($file = $this->getParametersFileByType('update'))
			return new $file();

		if($file = $this->getParametersFileByType('edit'))
			return new $file();

		$parameters = $this->getFormFieldsetsByType('update');

		return FieldsetParametersFile::makeByParameters($parameters);

		return null;
	}


}