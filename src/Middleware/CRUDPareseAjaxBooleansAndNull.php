<?php

namespace IlBronza\CRUD\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class CRUDPareseAjaxBooleansAndNull extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if($value === 'true' || $value === 'TRUE')
            return true;

        if($value === 'false' || $value === 'FALSE')
            return false;

        if($value === 'null' || $value === 'NULL')
            return null;

        return $value;
    }
}
