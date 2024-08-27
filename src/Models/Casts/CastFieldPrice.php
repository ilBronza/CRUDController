<?php

namespace IlBronza\CRUD\Models\Casts;

use IlBronza\Prices\Models\Price;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class CastFieldPrice implements CastsAttributes
{
	/**
	 * Cast the given value.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @param  string  $key
	 * @param  mixed  $value
	 * @param  array  $attributes
	 * @return mixed
	 */
	public function __construct(string $collectionId)
	{
		$this->collectionId = $collectionId;
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
//		$price = $model->providePriceByCollectionId($this->getCollectionId());
//		$price->price = $value;
//		$price->save();
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
//		if(! $this->collectionId)
//			return $model->getExtraAttribute('price');
//
		return $model->getCustomExtraAttribute($this->collectionId, 'price');
	}

}
