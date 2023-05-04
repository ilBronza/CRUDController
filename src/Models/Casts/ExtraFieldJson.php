<?php

namespace IlBronza\CRUD\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ExtraFieldJson implements CastsAttributes
{
    private function jsonField($value)
    {
        if(! $value)
            return [];

        return json_decode($value, true);
    }

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return $this->jsonField(
            $model->getExtraAttribute($key)
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        $model->extraFields->$key = $value;
    }
}
