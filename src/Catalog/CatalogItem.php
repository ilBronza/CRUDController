<?php

namespace IlBronza\CRUD\Catalog;

class CatalogItem
{
    public function __construct(
        public string|int $key,
        public string $title,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $url = null,
        public array $badges = [],
    ) {
    }

    /** Links in the default card accept HTTP(S), root-relative paths and anchors. */
    public static function safeUrl(?string $url): ?string
    {
        if ($url === null || preg_match('/[\x00-\x20\\\\]/', $url))
            return null;

        return preg_match('~^(https?://|/(?!/)|#)~i', $url) ? $url : null;
    }
}
