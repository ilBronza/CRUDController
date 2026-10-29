<?php

namespace IlBronza\CRUD\Sitemap;

use IlBronza\CRUD\Interfaces\SitemapProvider;

class SitemapGenerator
{
    protected SeoSitemapAdapter $adapter;

    public function __construct(?SeoSitemapAdapter $adapter = null)
    {
        $this->adapter = $adapter ?? new SeoSitemapAdapter;
    }

    /** Produces XML documents; the caller chooses storage and public URLs. */
    public function documents(iterable $providers, int $maxUrls = 50000): iterable
    {
        if ($maxUrls < 1 || $maxUrls > 50000)
            throw new \InvalidArgumentException('Sitemap size must be between 1 and 50000.');
        $header = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
        $xml = $header;
        $count = 0;
        $seen = [];
        foreach ($providers as $provider) {
            if (! $provider instanceof SitemapProvider)
                throw new \InvalidArgumentException('Expected a SitemapProvider.');
            foreach ($provider->items() as $model) {
                foreach ($this->adapter->entries($model) as $entry) {
                    if (isset($seen[$entry->url]))
                        continue;
                    $node = $this->node($entry);
                    if (strlen($header . $node . '</urlset>') > 52428800)
                        throw new \LengthException('A sitemap entry exceeds 50 MiB.');
                    if ($count && ($count >= $maxUrls || strlen($xml) + strlen($node) + 9 > 52428800)) {
                        yield $xml . '</urlset>';
                        $xml = $header;
                        $count = 0;
                    }
                    $seen[$entry->url] = true;
                    $xml .= $node;
                    $count++;
                }
            }
        }
        if ($count)
            yield $xml . '</urlset>';
    }

    public function index(iterable $sitemapUrls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $count = 0;
        $resolver = new \IlBronza\CRUD\Seo\SeoUrlResolver;
        foreach ($sitemapUrls as $url) {
            if (! $resolver->isAbsoluteUrl($url))
                throw new \InvalidArgumentException('Invalid sitemap URL.');
            $xml .= '<sitemap><loc>' . $this->escape($url) . '</loc></sitemap>';
            if (++$count > 50000 || strlen($xml . '</sitemapindex>') > 52428800)
                throw new \LengthException('Sitemap index exceeds protocol limits.');
        }
        return $xml . '</sitemapindex>';
    }

    protected function node(SitemapEntry $entry): string
    {
        $xml = '<url><loc>' . $this->escape($entry->url) . '</loc>';
        if ($entry->lastModified)
            $xml .= '<lastmod>' . $entry->lastModified->format(DATE_ATOM) . '</lastmod>';
        foreach ($entry->alternates as $locale => $url)
            $xml .= '<xhtml:link rel="alternate" hreflang="' . $this->escape($locale) . '" href="' . $this->escape($url) . '"/>';
        return $xml . '</url>';
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
