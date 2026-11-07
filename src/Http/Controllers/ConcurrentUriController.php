<?php

namespace IlBronza\CRUD\Http\Controllers;

use App\Http\Controllers\Controller;
use IlBronza\CRUD\Providers\ConcurrentUriChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConcurrentUriController extends Controller
{
    public function __construct(protected ConcurrentUriChecker $concurrentUriChecker)
    {
    }

    protected function context(Request $request): array
    {
        abort_unless(config('crud.useConcurrentRequestsAlert'), 404);
        abort_unless($request->user(), 401);
        $data = $request->validate([
            'url' => ['required', 'string', 'max:4096'],
            'pageKey' => ['required', 'string', 'regex:/^[a-zA-Z0-9_-]{20,128}$/'],
            'token' => ['required', 'string', 'max:16384'],
        ]);
        $path = $this->concurrentUriChecker->pathFromToken($data['token']);
        abort_unless($path === $data['url'], 403);

        return [$path, $data['pageKey']];
    }

    public function tick(Request $request): JsonResponse
    {
        return response()->json($this->concurrentUriChecker->managePrevalentUserData(...$this->context($request)))
            ->header('Cache-Control', 'no-store');
    }

    public function leavePage(Request $request): JsonResponse
    {
        return response()->json([
            'released' => $this->concurrentUriChecker->managePageLeaveByData(...$this->context($request)),
        ])->header('Cache-Control', 'no-store');
    }
}
