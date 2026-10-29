<?php

namespace IlBronza\CRUD\Interfaces;

interface SitemapProvider
{
    /** @return iterable<Sitemapable> Use lazy/chunked queries for large datasets. */
    public function items(): iterable;
}
