<?php

namespace IlBronza\CRUD\Models\Casts;

use IlBronza\Prices\Models\Price;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

use function dd;
use function explode;

class CastFieldPrice implements CastsAttributes
{
	public string $collectionId;
	public string $measurementUnit;

	/**
	 * Cast the given value.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @param  string  $key
	 * @param  mixed  $value
	 * @param  array  $attributes
	 * @return mixed
	 */
	public function __construct(string $collectionId, string $measurementUnit)
	{
		$this->collectionId = $collectionId;
		$this->measurementUnit = $measurementUnit;
	}

	public function getMeasurementUnit() : string
	{
		return $this->measurementUnit;
	}

	public function getCollectionId() : string
	{
		return $this->collectionId;
	}

	public function _get($model, string $key, $value, array $attributes)
	{
//		if(! $this->collectionId)
//			return $model->getExtraAttribute($key);
//
		return $model->getCustomExtraAttribute($this->collectionId, $key);
	}

	public function set($model, string $key, $value, array $attributes)
	{
		$price = $model->providePriceByCollectionId($this->getCollectionId());
		$price->setMeasurementUnit($this->getMeasurementUnit(), false);
		$price->price = $value;
	}

	static function staticSet(string $type = null, $model, string $key, $value)
	{
		throw new \Exception('staticSet method not implemented in ' . __CLASS__);
		//        if($value instanceof Carbon)
		//            $value = $value->format('Y-m-d H:i:s');
		//
		//        $caster = new static($type);
		//
		//        $caster->_set($model, $key, $value);
	}

	public function get($model, string $key, $value, array $attributes)
	{
		if(! $model->exists)
			return null;

		if($model->relationLoaded($this->getCollectionId()))
			return $model->{$this->getCollectionId()}->price;

		return $model->providePriceByCollectionId($this->getCollectionId())?->price;
	}

}
