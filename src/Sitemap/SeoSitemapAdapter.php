<?php

namespace IlBronza\CRUD\Sitemap;

use IlBronza\CRUD\Interfaces\Sitemapable;
use IlBronza\CRUD\Seo\SeoUrlResolver;

class SeoSitemapAdapter
{
    protected SeoUrlResolver $resolver;

    public function __construct(?SeoUrlResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new SeoUrlResolver;
    }

    public function entries(Sitemapable $model): iterable
    {
        if (! $model->isSitemapIndexable())
            return;
        $urls = [];
        foreach ($model->getSitemapUrls() as $locale => $url) {
            $sourceLocale = $locale;
            $locale = str_replace('_', '-', $locale);
            if (! preg_match('/^[a-zA-Z]{2,3}(-[a-zA-Z0-9]{2,8})*$/', $locale))
                throw new \InvalidArgumentException('Invalid sitemap locale.');
            if (! $this->resolver->isAbsoluteUrl($url))
                throw new \InvalidArgumentException('Sitemap URLs must be absolute HTTP(S) URLs without fragments.');
            if (! $this->resolver->isIndexable($model, $sourceLocale))
                continue;
            // Non-SEO sources use their supplied public URL directly.
            $canonical = method_exists($model, 'getSeo') ? $this->resolver->canonical($model, $sourceLocale) : $url;
            if ($canonical !== null && $canonical !== $url)
                continue;
            $urls[$locale] = $url;
        }
        foreach ($urls as $locale => $url)
            yield new SitemapEntry($url, $locale, $urls, $model->getSitemapLastModified());
    }
}
