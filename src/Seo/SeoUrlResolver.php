<?php

namespace IlBronza\CRUD\Seo;

class SeoUrlResolver
{
    public function canonical($model, string $locale): ?string
    {
        $seo = method_exists($model, 'getSeo') ? $model->getSeo($locale) : null;
        return ($seo->canonical_url ?? null) ?: $this->publicUrl($model, $locale);
    }

    public function publicUrl($model, string $locale): ?string
    {
        return $model->getDefaultSeoUrl($locale);
    }

    public function isIndexable($model, string $locale): bool
    {
        $seo = method_exists($model, 'getSeo') ? $model->getSeo($locale) : null;
        return ! preg_match('/\b(noindex|none)\b/i', $seo->robots ?? '');
    }

    public function isAbsoluteUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)
            && parse_url($url, PHP_URL_FRAGMENT) === null;
    }
}
