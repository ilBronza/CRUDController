<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Models\Seo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSeo
{
    public function seo(): MorphMany
    {
        return $this->morphMany(Seo::class, 'seoable');
    }

    public function getSeo(?string $locale = null): ?Seo
    {
        $locale = $locale ?? request()->attributes->get('frontend.locale', app()->getLocale());

        return $this->relationLoaded('seo')
            ? $this->getRelation('seo')->firstWhere('locale', $locale)
            : $this->seo()->where('locale', $locale)->first();
    }

    public function getDefaultSeoTitle(): ?string
    {
        return $this->getAttribute('title') ?? $this->getAttribute('name');
    }

    public function getDefaultSeoDescription(): ?string
    {
        return null;
    }

    // Uses getPublicUrl($locale) when the model exposes it; override otherwise.
    public function getDefaultSeoUrl(?string $locale = null): ?string
    {
        if (! method_exists($this, 'getPublicUrl')) {
            return null;
        }

        return $this->getPublicUrl($locale);
    }

    public function getDefaultSeoOgType(): string
    {
        return 'article';
    }

    public function getDefaultSeoSiteName(): ?string
    {
        return config('uikittemplate.head.siteName')
            ?? config('app.name');
    }

    /**
     * Optional share image defaults.
     *
     * @return array{url: string, width?: int|null, height?: int|null, alt?: string|null}|null
     */
    public function getDefaultSeoImage(?string $locale = null): ?array
    {
        return null;
    }

    public function getMetaTags(?string $locale = null): array
    {
        $locale = $locale ?? request()->attributes->get('frontend.locale', app()->getLocale());
        $seo = $this->getSeo($locale);
        $title = $seo->title ?? $this->getDefaultSeoTitle();
        $description = $seo->description ?? $this->getDefaultSeoDescription();
        $url = app(\IlBronza\CRUD\Seo\SeoUrlResolver::class)->canonical($this, $locale);
        $ogTitle = $seo->og_title ?? $title;
        $ogDescription = $seo->og_description ?? $description;
        $image = $this->resolveSeoImage($seo, $locale, $title);

        return array_filter([
            'title' => $title,
            'description' => $description,
            'canonical' => $url,
            'robots' => $seo->robots ?? 'index, follow',
            'og:title' => $ogTitle,
            'og:description' => $ogDescription,
            'og:url' => $url,
            'og:locale' => preg_match('/^[a-z]{2}[-_][A-Z]{2}$/', $locale) ? str_replace('-', '_', $locale) : null,
            'og:type' => $seo->og_type ?? $this->getDefaultSeoOgType(),
            'og:site_name' => $this->getDefaultSeoSiteName(),
            'og:image' => $image['url'] ?? null,
            'og:image:width' => $image['width'] ?? null,
            'og:image:height' => $image['height'] ?? null,
            'og:image:alt' => $image['alt'] ?? null,
            'twitter:card' => empty($image['url'] ?? null) ? 'summary' : 'summary_large_image',
            'twitter:title' => $ogTitle,
            'twitter:description' => $ogDescription,
            'twitter:image' => $image['url'] ?? null,
        ], static function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * @return array{url?: string, width?: int|null, height?: int|null, alt?: string|null}
     */
    private function resolveSeoImage(?Seo $seo, ?string $locale, ?string $fallbackAlt): array
    {
        if (! empty($seo?->og_image)) {
            return array_filter([
                'url' => $seo->og_image,
                'alt' => $seo->og_image_alt ?: $fallbackAlt,
            ], static fn ($value) => $value !== null && $value !== '');
        }

        $image = $this->getDefaultSeoImage($locale) ?? [];
        if ($image === []) {
            return [];
        }

        return array_filter([
            'url' => $image['url'] ?? null,
            'width' => $image['width'] ?? null,
            'height' => $image['height'] ?? null,
            'alt' => $seo?->og_image_alt ?: ($image['alt'] ?? $fallbackAlt),
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
