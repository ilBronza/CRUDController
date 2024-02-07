<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait CRUDUseUlidKeyTrait
{
    /**
     * The "booting" method of the model.
     */
    public static function bootCRUDUseUlidKeyTrait()
    {
        static::retrieved(function (Model $model) {
            $model->incrementing = false;  // this is used after instance is loaded from DB
        });

        static::creating(function (Model $model) {
            $model->incrementing = false; // this is used for new instances

            if (empty($model->{$model->getKeyName()})) { // if it's not empty, then we want to use a specific id
                $model->{$model->getKeyName()} = Str::ulid()->toBase32();
            }
        });
    }

    public function initializeUuidForKey()
    {
        $this->keyType = 'string';
    }
}