<?php

namespace IlBronza\CRUD\Models\Casts;

use IlBronza\FormField\Casts\JsonFieldCast;

class ExtraFieldJson extends JsonFieldCast
{
    public $extraModelClassname;

    /**
     * Come usare ExtraField
     * 
     * vedere ExtraField.php
     * 
     **/



    public function __construct(string $extraModelClassname = null)
    {
        $this->extraModelClassname = $extraModelClassname;
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
        if(! $this->extraModelClassname)
            return $this->jsonField(
                $model->getExtraAttribute($key)
            );

        return $this->jsonField(
            $model->getCustomExtraAttribute($this->extraModelClassname, $key)
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
        return $this->_set($model, $key, $value, $attributes);
    }

    public function _set($model, string $key, $value, array $attributes = null)
    {
        if(! $this->extraModelClassname)
        {
            // if(! ($model->relationLoaded('extraFields')))
            $extraFields = $model->getCachedProjectExtraFieldsModel();

            try
            {
                $extraFields->$key = $value;

                unset($model->$key);
            }
            catch(\Exception $e)
            {

                dddl($e);
            }

            return ;
        }

        $extraModelClassname = $this->extraModelClassname;

        if(! $model->$extraModelClassname)
            $model->$extraModelClassname = $model->provideExtraFieldCustomModel($extraModelClassname);

        $model->$extraModelClassname->$key = $value;       
    }
}
