<?php

namespace IlBronza\CRUD\Traits\ConcurrentUri;

use InvalidArgumentException;

trait ConcurrentUriGettersAndSetters
{
    public function getJavascriptTickInterval(): int
    {
        return $this->positiveIntegerConfig('concurrentUriJavascriptTickInterval', 3000);
    }

    public function getCacheLifetime(): int
    {
        $seconds = $this->positiveIntegerConfig('concurrentUriCacheLifetime', 10);
        if ($seconds * 1000 < $this->getJavascriptTickInterval() * 3) {
            throw new InvalidArgumentException('crud.concurrentUriCacheLifetime must cover at least three heartbeat intervals.');
        }

        return $seconds;
    }

    protected function positiveIntegerConfig(string $key, int $default): int
    {
        $value = filter_var(config('crud.' . $key, $default), FILTER_VALIDATE_INT);
        if ($value === false || $value < 1) {
            throw new InvalidArgumentException('crud.' . $key . ' must be a positive integer.');
        }

        return $value;
    }

    public function getBasePathKey(): string
    {
        return request()->path();
    }

    public function getScope(): string
    {
        return request()->getSchemeAndHttpHost() . '|' . config('crud.concurrentUriScope', '');
    }

    public function getUriCacheKey(?string $path = null): string
    {
        // Version the format so old serialized records cannot be treated as leases.
        return config('crud.concurrentUriPrefix', 'concurrentUri') . ':v2:'
            . hash('sha256', json_encode([$this->getScope(), $path ?? $this->getBasePathKey()], JSON_THROW_ON_ERROR));
    }
}
