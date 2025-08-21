<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

trait CRUDUseUuidTrait
{
    public function crudModelSetId()
    {
        //TODO verificare se esiste un metodo di sistema
        $this->{$this->getKeyName()} = (string) Uuid::uuid4();
    }

    /**
     * The "booting" method of the model.
     */
    public static function bootCRUDUseUuidTrait()
    {
        static::retrieved(function (Model $model) {
            $model->incrementing = false;  // this is used after instance is loaded from DB
        });

        static::creating(function (Model $model) {
            $model->incrementing = false; // this is used for new instances

            if (empty($model->{$model->getKeyName()})) { // if it's not empty, then we want to use a specific id
                $model->crudModelSetId();
            }
        });
    }

    public function initializeUuidForKey()
    {
		//TODO questo non viene più invocato controllare
        $this->keyType = 'string';
    }
}