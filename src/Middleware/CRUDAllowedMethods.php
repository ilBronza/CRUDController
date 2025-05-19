<?php

namespace IlBronza\CRUD\Middleware;

use Illuminate\Support\Facades\Route;
use Closure;

class CRUDAllowedMethods
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ... $allowedMethods)
    {
        if(! in_array(Route::current()->getActionMethod(), $allowedMethods))
            abort(403, get_class($this) .  ' - Method ' . Route::current()->getActionMethod() . ' not declared');

        return $next($request);
    }
}
