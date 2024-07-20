<?php

namespace IlBronza\CRUD\Middleware;

use Carbon\Carbon;
use Closure;
use IlBronza\CRUD\CRUDRoutingHelper;
use IlBronza\Ukn\Facades\Ukn;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class CRUDCheckForcedUrlMiddleware
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
        // Log::info('richiesto: ' . $request->requestUri);

        // if(session()->get('allowPass'))
        // {
        //     Log::info('qua');
        //     return $next($request);
        // }

        // Log::info('passato: ' . $request->requestUri);

        // if(CRUDRoutingHelper::hasForcedUrl())
        // {
        //     $forcedUrl = CRUDRoutingHelper::popForcedUrl();

        //     if($message = $forcedUrl->getMessage())
        //         Ukn::w($message);

        //     return redirect()->to(
        //         $forcedUrl->getUrl()
        //     )->with(
        //         'allowPass',
        //         Carbon::now()
        //     );
        // }

        return $next($request);
    }
}
