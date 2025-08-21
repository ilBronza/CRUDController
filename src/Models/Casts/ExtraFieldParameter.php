<?php

namespace IlBronza\CRUD\Models\Casts;

class ExtraFieldParameter extends ExtraField
{
	public $extraModelClassname;
	public $parametersFieldName;
	public $default;

	public function __construct(string $extraModelClassname = null, ? string $parametersFieldName, mixed $default)
	{
		$this->extraModelClassname = $extraModelClassname;
		$this->parametersFieldName = $parametersFieldName;
		$this->default = $default;
	}

	public function getParameters() : array
	{
		return $model->{$this->parametersFieldName} ?? [];
	}

	public function set($model, string $key, $value, array $attributes)
	{
		$parameters = $this->getParameters($model);

		$parameters[$key] = $value;

		return $this->_set($model, $this->parametersFieldName, $parameters, $attributes);
	}

	public function get($model, string $key, $value, array $attributes)
	{
		$parameters = $this->getParameters($model);

		return $parameters[$key] ?? $this->default;
	}
}
