<?php

namespace IlBronza\CRUD\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class IframeCheckerMiddleware
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
		if((( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe'))||($request->has('iframe'))||($request->has('iframed')))
        {
            View::share('ib_IFRAMED', true);
            // define('__ib_IFRAMED__', true);
        }

		else
        {
            View::share('ib_IFRAMED', false);
            // define("__ib_IFRAMED__", false);            
        }

		return $next($request);
    }
}
