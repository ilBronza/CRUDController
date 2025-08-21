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
            abort(403, 'Method ' . Route::current()->getActionMethod() . ' not declared on ' . Route::current()->getAction()['controller']);

        return $next($request);
    }
}
