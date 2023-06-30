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

}
