<?php

namespace IlBronza\CRUD\Models\Casts;

use IlBronza\Prices\Models\Price;

class ExtraFieldPrice extends ExtraField
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
	public function set($model, string $key, $value, array $attributes)
	{
		//        if($value instanceof Price)
		//            $value = $value->price;

		return $this->_set($model, 'price', $value, $attributes);
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
		if(! $this->extraModelClassname)
			return $model->getExtraAttribute('price');

		return $model->getCustomExtraAttribute($this->extraModelClassname, 'price');
	}

}
