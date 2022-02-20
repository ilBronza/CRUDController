<?php

namespace IlBronza\CRUD\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class CRUDParseComasAndDots extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if(is_string($value))
        {
            $re = '/^\d+(?:,\d+)*$/';

            if (preg_match($re, $value))
                return strval(floatval(str_replace(',', '.', $value)));
        }

        return $value;
    }
}
