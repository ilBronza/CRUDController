<?php

namespace IlBronza\CRUD\Sitemap;

use IlBronza\CRUD\Enums\SitemapChangeFrequency;
use IlBronza\CRUD\Interfaces\Sitemapable;

/** Lightweight Sitemapable for static pages and ad-hoc URL sets. */
class SitemapPage implements Sitemapable
{
    /** @param array<string, string> $urls locale => absolute URL */
    public function __construct(
        private readonly array $urls,
        private readonly ?\DateTimeInterface $lastModified = null,
        private readonly bool $indexable = true,
        private readonly ?SitemapChangeFrequency $changeFrequency = null,
        private readonly ?float $priority = null,
    ) {}

    public function getSitemapUrls(): iterable
    {
        return $this->urls;
    }

    public function getSitemapLastModified(): ?\DateTimeInterface
    {
        return $this->lastModified;
    }

    public function isSitemapIndexable(): bool
    {
        return $this->indexable && $this->urls !== [];
    }

    public function getSitemapChangeFrequency(): ?SitemapChangeFrequency
    {
        return $this->changeFrequency;
    }

    public function getSitemapPriority(): ?float
    {
        return $this->priority;
    }
}
