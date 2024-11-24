<?php

namespace IlBronza\CRUD\Models\Casts;

use Carbon\Carbon;

class ExtraFieldDate extends ExtraField
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
        if($value instanceof Carbon)
            $value = $value->format('Y-m-d H:i:s');

        return $this->_set($model, $key, $value, $attributes);
    }

    static function staticSet(string $type = null, $model, string $key, $value)
    {
        if($value instanceof Carbon)
            $value = $value->format('Y-m-d H:i:s');

        $caster = new static($type);

        $caster->_set($model, $key, $value);
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if(! $value = $this->_get($model, $key, $value, $attributes))
            return $value;

        if($value instanceof Carbon)
            return $value;

		try
        {
			if(strlen($value) == '10')
				return Carbon::createFromFormat('Y-m-d', $value);

            return Carbon::createFromFormat('Y-m-d H:i:s', $value);
        }
        catch(\Exception $e)
        {
            return $value;
        }
    }

}
