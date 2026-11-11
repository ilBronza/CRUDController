<?php

namespace IlBronza\CRUD\Middleware;

use Closure;
use IlBronza\CRUD\Providers\ConcurrentUriChecker;
use Illuminate\Support\Facades\Validator;

class CRUDConcurrentUrlAlert
{
    public function __construct(protected ConcurrentUriChecker $concurrentUriChecker)
    {
    }

    public function handle($request, Closure $next)
    {
        if (! config('crud.useConcurrentRequestsAlert')) {
            return $next($request);
        }

        abort_unless($request->user(), 401);
        if (! $request->isMethodSafe()) {
            $context = Validator::make([
                'token' => $request->header('X-CRUD-Concurrent-Token', $request->input('_crud_concurrent_token')),
                'pageKey' => $request->header('X-CRUD-Page-Key', $request->input('_crud_page_key')),
            ], [
                'token' => ['required', 'string', 'max:16384'],
                'pageKey' => ['required', 'string', 'regex:/^[a-zA-Z0-9_-]{20,128}$/'],
            ])->validate();

            $path = $this->concurrentUriChecker->pathFromToken($context['token']);
            return $this->concurrentUriChecker->runOwnedOperation($path, $context['pageKey'],
                fn () => $this->pageResponse($request, $next));
        }

        return $this->pageResponse($request, $next);
    }

    protected function pageResponse($request, Closure $next)
    {
        // POST actions can render iframe pages too. Bootstrap their client before
        // the page's own AJAX starts, while keeping the incoming ownership guard.
        $request->attributes->set('crud.concurrentUri', $this->concurrentUriChecker->getPageContext());
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
