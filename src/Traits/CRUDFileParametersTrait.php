<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

use function dd;

trait CRUDFileParametersTrait
{
	public function getGenericParametersFile() : ? string
	{
		if($this->parametersFile ?? null)
			return $this->parametersFile;

		return null;
	}

	private function getParametersFileByType(string $type, bool $strict = false) : ? string
	{
		$propertyName = "{$type}ParametersFile";

		if($this->$propertyName ?? null)
			return $this->$propertyName;

		$propertyMethod = "get" . ucfirst($type) . "ParametersFile";

		if(method_exists($this, $propertyMethod))
			return $this->{$propertyMethod}();

		if($strict)
			return null;

		return $this->getGenericParametersFile();
	}

	public function getTeaserParametersClass() : FieldsetParametersFile
	{
		if(! $file = $this->getParametersFileByType('teaser'))
			$file = $this->getGenericParametersFile();

		return new $file();
	}

	public function getShowParametersClass() : ? FieldsetParametersFile
	{
		if($file = $this->getParametersFileByType('show'))
			return new $file();

		if($file = $this->getGenericParametersFile())
			return new $file();

		return null;
	}

	public function getCreateParametersClass() : FieldsetParametersFile
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

	public function getEditParametersClass() : FieldsetParametersFile
	{
		if($file = $this->getParametersFileByType('edit'))
			return new $file();

		$parameters = $this->getFormFieldsetsByType('edit');

		return FieldsetParametersFile::makeByParameters($parameters);
	}

	public function getUpdateParametersClass() : ? FieldsetParametersFile
	{
		if($file = $this->getParametersFileByType('update', $strict = true))
			return new $file();

		if($file = $this->getParametersFileByType('edit'))
			return new $file();

		$parameters = $this->getFormFieldsetsByType('update');

		return FieldsetParametersFile::makeByParameters($parameters);

		return null;
	}


}