<?php

namespace IlBronza\CRUD\Sitemap;

use IlBronza\CRUD\Enums\SitemapChangeFrequency;

class SitemapEntry
{
    public string $url;
    public string $locale;
    public array $alternates;
    public ?\DateTimeInterface $lastModified;
    public ?SitemapChangeFrequency $changeFrequency;
    public ?float $priority;

    public function __construct(
        string $url,
        string $locale,
        array $alternates = [],
        ?\DateTimeInterface $lastModified = null,
        ?SitemapChangeFrequency $changeFrequency = null,
        ?float $priority = null,
    ) {
        if ($priority !== null && ($priority < 0.0 || $priority > 1.0)) {
            throw new \InvalidArgumentException('Sitemap priority must be between 0.0 and 1.0.');
        }

        $this->url = $url;
        $this->locale = $locale;
        $this->alternates = $alternates;
        $this->lastModified = $lastModified;
        $this->changeFrequency = $changeFrequency;
        $this->priority = $priority;
    }
}
