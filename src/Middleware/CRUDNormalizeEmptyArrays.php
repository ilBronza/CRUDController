<?php

namespace IlBronza\CRUD\Middleware;

use Closure;
use Illuminate\Http\Request;

class CRUDNormalizeEmptyArrays
{
    public function handle(Request $request, Closure $next)
    {
		$input = $request->all();

		if(! isset($input['value']))
			return $next($request);

		if(! is_array($input['value']))
			return $next($request);

        $value = array_filter($input['value'], function ($v) {
            return $v !== null;
        });

        // If after filtering nulls the array is empty, normalize to []
        if (count($value) === 0) {
            $input['value'] = [];
            $request->replace($input);
        }

        return $next($request);
    }
}