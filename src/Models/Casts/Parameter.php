<?php

namespace IlBronza\CRUD\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

use function json_decode;
use function json_encode;

class Parameter implements CastsAttributes
{
	public $extraModelClassname;
	public $parametersFieldName;
	public $default;

	public function __construct(? string $parametersFieldName)
	{
		$this->parametersFieldName = $parametersFieldName;
	}

	public function getParameters($model, array $attributes) : array
	{
		return json_decode( $attributes[$this->parametersFieldName] ?? null, true) ?? [];
	}

	public function setParameters(array $attributes, array $parameters) : array
	{
		$attributes[$this->parametersFieldName] = json_encode($parameters);

		return $attributes;
	}

	public function set($model, string $key, $value, array $attributes)
	{
		$parameters = $this->getParameters($model, $attributes);

		$parameters[$key] = $value;

		return $this->setParameters($attributes, $parameters);
	}

	public function get($model, string $key, $value, array $attributes)
	{
		$parameters = $this->getParameters($model, $attributes);

		return $parameters[$key] ?? null;
	}
}
