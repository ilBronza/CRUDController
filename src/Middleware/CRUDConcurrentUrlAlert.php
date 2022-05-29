<?php

namespace IlBronza\CRUD\Middleware;

use Closure;
use IlBronza\CRUD\Providers\ConcurrentUriChecker;
use Illuminate\Support\Facades\Route;

class CRUDConcurrentUrlAlert
{
    public function __construct(protected ConcurrentUriChecker $concurrentUriChecker)
    {

    }

    public function handle($request, Closure $next)
    {
        view()->share('checkConcurrentUri', true);

        return $next($request);
    }
}
