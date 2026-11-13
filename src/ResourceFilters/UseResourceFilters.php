<?php

namespace IlBronza\CRUD\ResourceFilters;

use Closure;
use Illuminate\Http\Request;

class UseResourceFilters
{
    public function handle(Request $request, Closure $next): mixed
    {
        return app(ResourceFilters::class)->duringRequest($request, $next);
    }
}
