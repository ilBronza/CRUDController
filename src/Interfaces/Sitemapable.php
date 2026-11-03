<?php

namespace IlBronza\CRUD\Interfaces;

use IlBronza\CRUD\Enums\SitemapChangeFrequency;

interface Sitemapable
{
    /** Published public translations only: locale => absolute URL. */
    public function getSitemapUrls(): iterable;

    public function getSitemapLastModified(): ?\DateTimeInterface;

    public function isSitemapIndexable(): bool;

    public function getSitemapChangeFrequency(): ?SitemapChangeFrequency;

    /** Relative importance from 0.0 to 1.0, or null to omit. */
    public function getSitemapPriority(): ?float;
}
