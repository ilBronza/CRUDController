<?php

namespace IlBronza\CRUD\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConcurrentUriData
{
    public int|string|null $id;
    public string $pageKey;
    /** @var array<string, int> Expiration time for each admitted tab. */
    public array $pages = [];

    public function __construct()
    {
        $this->id = Auth::id();
        $this->pageKey = Str::random(40);
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getPageKey(): string
    {
        return $this->pageKey;
    }

    public function setPageKey(string $pageKey): void
    {
        $this->pageKey = $pageKey;
    }

    public function prune(int $now): void
    {
        $this->pages = array_filter($this->pages, fn (int $expires) => $expires > $now);
        if ($this->pages && ! isset($this->pages[$this->pageKey])) {
            $this->pageKey = (string) array_key_first($this->pages);
        }
    }
}
