<?php

namespace IlBronza\CRUD\Middleware;

/**
 * if user cant delete anything abort
 * foreach argument, if exists permission to delete that argument, the middleware check if the user has that permission, if not, abort
 * if exists a method to check if a user can delete that element type, the user is checked, and if can edit that model, the foreach cycle continue to next parameter to check. Otherwhise it aborts.
 * if specific method for this model type doesnt exist, the middleware check if model's user_id is equal to logged user, if not abort
 */

use Closure;
use IlBronza\CRUD\Helpers\RouteHelpers\RouteHelper;

class CRUDReturnBackMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ... $args)
    {
		$urlKey = RouteHelper::getUrlReturnBackKey($request->url());

//		echo "UrlKey: " . $urlKey . "<br />";

		    if ($controllerKey = session($urlKey))
			{
//				echo "controllerKey: " . $controllerKey . "<br />";

			    // burn reverse
			    session()->forget($urlKey);
			    // burn forward (controllerKey -> url)
			    session()->forget($controllerKey);
		    }

	    return $next($request);

    }
}
