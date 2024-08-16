<?php

namespace IlBronza\CRUD\Middleware;

use Illuminate\Support\Facades\Route;
use Closure;

use function define;

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
		if((( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe'))||($request->has('iframe')))
			define("__ib_IFRAMED__", true);

		else
			define("__ib_IFRAMED__", false);

		return $next($request);
    }
}
