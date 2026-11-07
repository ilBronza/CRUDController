<?php

namespace IlBronza\CRUD\Sitemap;

use IlBronza\CRUD\Enums\SitemapChangeFrequency;
use IlBronza\CRUD\Interfaces\Sitemapable;
use Illuminate\Database\Eloquent\Model;

/**
 * Adapts a model that exposes getPublicUrl() or getDefaultSeoUrl() to Sitemapable.
 */
class PublicUrlSitemapItem implements Sitemapable
{
    public function __construct(
        private readonly Model $model,
        private readonly array $query = [],
        private readonly ?string $locale = null,
        private readonly ?SitemapChangeFrequency $changeFrequency = null,
        private readonly ?float $priority = null,
    ) {}

    public function getSitemapUrls(): iterable
    {
        $locales = $this->locale !== null
            ? [$this->locale]
            : (array) config('uikittemplate.availableLanguages', [config('app.locale', 'en')]);

        foreach ($locales as $locale) {
            if (! is_string($locale) || $locale === '') {
                continue;
            }
            $url = $this->resolveUrl($locale);
            if (is_string($url) && $url !== '' && ! str_contains($url, '#')) {
                yield $locale => $url;
            }
        }
    }

    public function getSitemapLastModified(): ?\DateTimeInterface
    {
        $value = $this->model->getAttribute('updated_at');

        return $value instanceof \DateTimeInterface ? $value : null;
    }

    public function isSitemapIndexable(): bool
    {
        foreach ($this->getSitemapUrls() as $url) {
            return true;
        }

        return false;
    }

    public function getSitemapChangeFrequency(): ?SitemapChangeFrequency
    {
        return $this->changeFrequency;
    }

    public function getSitemapPriority(): ?float
    {
        return $this->priority;
    }

    private function resolveUrl(string $locale): ?string
    {
        if (method_exists($this->model, 'getPublicUrl')) {
            $url = $this->model->getPublicUrl($locale, $this->query);

            return is_string($url) ? $url : null;
        }

        if (method_exists($this->model, 'getDefaultSeoUrl')) {
            $url = $this->model->getDefaultSeoUrl($locale);

            return is_string($url) ? $url : null;
        }

        return null;
    }
}
