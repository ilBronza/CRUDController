<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;

trait CRUDUserOwningsTrait
{
    static function getUserKeyField()
    {
        return static::$userKey ?? 'user_id';
    }

    public function scopeByUser($query)
    {
        return $query->where(
            static::getUserKeyField(),
            Auth::id()
        );
    }
}