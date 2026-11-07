<?php

namespace IlBronza\CRUD\Providers;

use Closure;
use IlBronza\CRUD\Helpers\ConcurrentUriData;
use IlBronza\CRUD\Traits\ConcurrentUri\ConcurrentUriGettersAndSetters;
use Illuminate\Cache\NullStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use JsonException;
use LogicException;

class ConcurrentUriChecker
{
    use ConcurrentUriGettersAndSetters;

    public function checksSameUserTabs(): bool
    {
        return (bool) config('crud.concurrentUriCheckSameUserTabs', true);
    }

    protected function cache()
    {
        $cache = Cache::store(config('crud.concurrentUriCacheStore'));
        if (! $cache->getStore() instanceof LockProvider || $cache->getStore() instanceof NullStore) {
            throw new LogicException('Concurrent URI checking requires a cache store with atomic locks.');
        }

        return $cache;
    }

    public function withPageLock(string $path, Closure $callback, bool $operation = false)
    {
        $seconds = $operation
            ? $this->positiveIntegerConfig('concurrentUriOperationLockLifetime', 120)
            : 10;
        $lock = $this->cache()->lock($this->getUriCacheKey($path) . ':lock', $seconds);
        abort_unless($lock->get(), 423, trans('crud::crud.concurrentUriBusy'));

        try {
            return $callback($lock);
        } finally {
            $lock->release();
        }
    }

    public function getPrevalentUserDataByPath(string $path): ?ConcurrentUriData
    {
        $data = $this->cache()->get($this->getUriCacheKey($path));
        if (! $data instanceof ConcurrentUriData) {
            return null;
        }

        // Array caches may return the same object; reads must not mutate stored state.
        $data = clone $data;
        $data->prune(now()->getTimestamp());

        return $data->pages ? $data : null;
    }

    public function getPrevalentUserData(): ?ConcurrentUriData
    {
        return $this->getPrevalentUserDataByPath($this->getBasePathKey());
    }

    public function check(string $path): ?ConcurrentUriData
    {
        return $this->getPrevalentUserDataByPath($path);
    }

    protected function persist(string $path, ConcurrentUriData $data): void
    {
        abort_unless($this->cache()->put($this->getUriCacheKey($path), $data, $this->getCacheLifetime()), 503,
            trans('crud::crud.concurrentUriOffline'));
    }

    protected function status(?ConcurrentUriData $data, string $pageKey): string
    {
        if (! $data) {
            return 'expired';
        }
        if ((string) $data->id !== (string) Auth::id()) {
            return 'notAllowed';
        }
        if ($this->checksSameUserTabs() && $data->pageKey !== $pageKey) {
            return 'morePages';
        }

        return 'kept';
    }

    protected function response(string $status, ?ConcurrentUriData $data): array
    {
        $messages = [
            'gained' => 'theOtherUserClosedThisUriBetterToRefreshPageToSeeUpdates',
            'kept' => 'youOwnThisUri',
            'morePages' => 'youAlreadyOpenedThisUrlOnADifferentPage',
            'notAllowed' => 'aDifferentUserIsWatchingThisPage',
            'expired' => 'concurrentUriExpired',
        ];

        return [
            // A visitor never receives another tab's ownership credential.
            'userData' => $data ? ['id' => $data->id] : null,
            'status' => $status,
            'message' => trans('crud::crud.' . $messages[$status]),
        ];
    }

    public function managePrevalentUserData(string $path, string $pageKey): array
    {
        abort_unless(Auth::check(), 401);
        $lifetime = $this->getCacheLifetime();

        return $this->withPageLock($path, function () use ($path, $pageKey, $lifetime) {
            $data = $this->getPrevalentUserDataByPath($path);
            $status = $this->status($data, $pageKey);
            if (! $data) {
                $data = new ConcurrentUriData();
                $data->setPageKey($pageKey);
                $status = 'gained';
            }

            if (in_array($status, ['gained', 'kept'], true)) {
                $data->pages[$pageKey] = now()->getTimestamp() + $lifetime;
                $this->persist($path, $data);
            }

            return $this->response($status, $data);
        });
    }

    public function managePageLeaveByData(string $path, string $pageKey): bool
    {
        abort_unless(Auth::check(), 401);

        return $this->withPageLock($path, function () use ($path, $pageKey) {
            $data = $this->getPrevalentUserDataByPath($path);
            if (! $data || (string) $data->id !== (string) Auth::id()) {
                return true;
            }

            unset($data->pages[$pageKey]);
            $data->prune(now()->getTimestamp());
            if ($data->pages) {
                $this->persist($path, $data);
            } else {
                $this->cache()->forget($this->getUriCacheKey($path));
            }

            return true;
        });
    }

    public function runOwnedOperation(string $path, string $pageKey, Closure $operation)
    {
        abort_unless(Auth::check(), 401);

        return $this->withPageLock($path, function ($lock) use ($path, $pageKey, $operation) {
            $data = $this->getPrevalentUserDataByPath($path);
            $status = $this->status($data, $pageKey);
            // A write cannot acquire ownership, even after a lease expires.
            if ($status === 'kept' && ! isset($data->pages[$pageKey])) {
                $status = 'expired';
            }
            abort_unless($status === 'kept', 409, $this->response($status, $data)['message']);

            try {
                return $operation();
            } finally {
                // A request exceeding the configured lock duration must not overwrite a new owner.
                if ($lock->isOwnedByCurrentProcess()) {
                    $data->pages[$pageKey] = now()->getTimestamp() + $this->getCacheLifetime();
                    $this->persist($path, $data);
                }
            }
        }, true);
    }

    public function getPageContext(): array
    {
        abort_unless(Auth::check(), 401);
        $path = $this->getBasePathKey();

        return [
            'url' => $path,
            // The tab generates its own key on every document load, including duplicated tabs.
            'token' => Crypt::encryptString(json_encode([
                'path' => $path,
                'user' => (string) Auth::id(),
                'scope' => $this->getScope(),
            ], JSON_THROW_ON_ERROR)),
            'tickUrl' => route('concurrentUri.tick'),
            'leaveUrl' => route('concurrentUri.leavePage'),
            'interval' => $this->getJavascriptTickInterval(),
            'lifetime' => $this->getCacheLifetime() * 1000,
            'messages' => [
                'checking' => trans('crud::crud.concurrentUriChecking'),
                'offline' => trans('crud::crud.concurrentUriOffline'),
                'expired' => trans('crud::crud.concurrentUriExpired'),
                'owner' => trans('crud::crud.youOwnThisUri'),
                'gained' => trans('crud::crud.theOtherUserClosedThisUriBetterToRefreshPageToSeeUpdates'),
            ],
        ];
    }

    public function pathFromToken(string $token): string
    {
        abort_unless(Auth::check(), 401);
        try {
            $data = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException $exception) {
            abort(403, 'Invalid concurrent page context.');
        }

        abort_unless(is_array($data)
            && isset($data['path'], $data['user'], $data['scope'])
            && is_string($data['path'])
            && $data['user'] === (string) Auth::id()
            && $data['scope'] === $this->getScope(), 403, 'Invalid concurrent page context.');

        return $data['path'];
    }
}
