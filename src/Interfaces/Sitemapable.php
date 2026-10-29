<?php

namespace IlBronza\CRUD\Interfaces;

interface Sitemapable
{
    /** Published public translations only: locale => absolute URL. */
    public function getSitemapUrls(): iterable;

    public function getSitemapLastModified(): ?\DateTimeInterface;

    public function isSitemapIndexable(): bool;
}
