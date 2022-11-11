<?php

namespace IlBronza\CRUD\Traits\Model;

use Carbon\Carbon;

trait CRUDValidityDatesTrait
{
    public function scopeValidByDate($query, Carbon $date = null)
    {
        if(! $date)
            $date = Carbon::now();

        return $query->where(function($_query)
            {
                return $_query->where('valid_from', '<', Carbon::now())->orWhereNull('valid_from');
            })->where(function($_query)
            {
                return $_query->where('valid_to', '>', Carbon::now())->orWhereNull('valid_to');                
            });
    }
}