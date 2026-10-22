<?php

namespace IlBronza\CRUD\Sitemap;

class SitemapEntry
{
    public string $url;
    public string $locale;
    public array $alternates;
    public ?\DateTimeInterface $lastModified;

    public function __construct(string $url, string $locale, array $alternates = [], ?\DateTimeInterface $lastModified = null)
    {
        $this->url = $url;
        $this->locale = $locale;
        $this->alternates = $alternates;
        $this->lastModified = $lastModified;
    }
}
