<?php

namespace IlBronza\CRUD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MetaManager
{
	public $meta = [];

	public function setMeta(array $meta)
	{
		foreach($meta as $name => $value)
			$this->addMeta($name, $value);
	}

	public function addMeta(string $name, $value)
	{
		$this->meta[$name] = $value;
	}

	public function getMeta()
	{
		return $this->meta;
	}

	public function getMandatoryMetaNames() : array
	{
		return config('crud.meta.mandatoryNames');
	}

	public function checkMandatoryMeta()
	{
		$names = $this->getMandatoryMetaNames();

		foreach($names as $name)
			if(! isset($this->meta[$name]))
				$this->meta[$name] = trans('meta.' . $name . "_" . Str::slug(app('uikittemplate')->getPageTitle()));
	}

	public function setByModel(Model $model)
	{
		foreach($model->getMetaTags() as $name => $value)
			$this->addMeta($name, $value);
	}

	public function renderMeta()
	{
		$this->checkMandatoryMeta();

		return view('crud::utilities.meta');
	}
}