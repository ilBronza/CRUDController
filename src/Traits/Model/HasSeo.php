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
        $locale = $locale ?? app()->getLocale();

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

    // Override with the public, localized URL of the entity.
    public function getDefaultSeoUrl(?string $locale = null): ?string
    {
        return null;
    }

    public function getMetaTags(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $seo = $this->getSeo($locale);
        $title = $seo->title ?? $this->getDefaultSeoTitle();
        $description = $seo->description ?? $this->getDefaultSeoDescription();
        $url = app(\IlBronza\CRUD\Seo\SeoUrlResolver::class)->canonical($this, $locale);
        $ogTitle = $seo->og_title ?? $title;
        $ogDescription = $seo->og_description ?? $description;

        return array_filter([
            'title' => $title,
            'description' => $description,
            'canonical' => $url,
            'robots' => $seo->robots ?? 'index, follow',
            'og:title' => $ogTitle,
            'og:description' => $ogDescription,
            'og:url' => $url,
            'og:locale' => preg_match('/^[a-z]{2}[-_][A-Z]{2}$/', $locale) ? str_replace('-', '_', $locale) : null,
            'og:type' => $seo->og_type ?? 'website',
            'og:image' => $seo->og_image ?? null,
            'og:image:alt' => $seo->og_image_alt ?? null,
            'twitter:card' => empty($seo->og_image) ? 'summary' : 'summary_large_image',
            'twitter:title' => $ogTitle,
            'twitter:description' => $ogDescription,
            'twitter:image' => $seo->og_image ?? null,
            'twitter:image:alt' => $seo->og_image_alt ?? null,
        ], static function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
