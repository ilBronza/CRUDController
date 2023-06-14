<?php

namespace IlBronza\CRUD\Models\Casts;

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
    public function get($model, string $key, $value, array $attributes) : ? string
    {
        if(! $this->extraModelClassname)
            return $model->getExtraAttribute($key);

        return $model->getCustomExtraAttribute($this->extraModelClassname, $key);
    }
}
