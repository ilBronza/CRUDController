<?php

namespace IlBronza\CRUD\Sitemap;

use IlBronza\CRUD\Interfaces\SitemapProvider;
use Illuminate\Contracts\Container\Container;

class SitemapWriter
{
    public function __construct(
        private readonly ?Container $container = null,
        private readonly ?SitemapGenerator $generator = null,
    ) {}

    /**
     * Write sitemap.xml (index) and named urlset files.
     *
     * @param  array<string, class-string|callable|SitemapProvider>|null  $providers
     * @return list<string> Absolute paths of written files.
     */
    public function write(?string $path = null, ?array $providers = null): array
    {
        $path ??= config('crud.sitemap.path') ?: public_path('sitemap.xml');
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $generator = $this->generator ?? new SitemapGenerator(
            new SeoSitemapAdapter,
            (bool) config('crud.sitemap.include_hreflang_alternates', false),
        );
        $this->cleanupPrevious($path);

        $written = [];
        $indexUrls = [];

        foreach ($this->entries($providers) as $name => $provider) {
            $documents = iterator_to_array($generator->documents([$provider]), false);
            if ($documents === []) {
                continue;
            }

            foreach ($documents as $index => $xml) {
                $basename = count($documents) === 1
                    ? "sitemap-{$name}.xml"
                    : "sitemap-{$name}-".($index + 1).'.xml';
                $chunkPath = $directory.DIRECTORY_SEPARATOR.$basename;
                $this->atomicPut($chunkPath, $xml);
                $written[] = $chunkPath;
                $indexUrls[] = url($basename);
            }
        }

        $this->atomicPut($path, $generator->index($indexUrls));
        $written[] = $path;

        return $written;
    }

    /**
     * @param  array<string, class-string|callable|SitemapProvider>|null  $providers
     * @return array<string, SitemapProvider>
     */
    protected function entries(?array $providers = null): array
    {
        $providers ??= config('crud.sitemap.providers', []);
        if ($providers === []) {
            return [];
        }

        $groupByLocale = (bool) config('crud.sitemap.group_by_locale', true);
        if (! $groupByLocale) {
            $entries = [];
            foreach ($providers as $name => $provider) {
                $entries[(string) $name] = $this->makeProvider($provider, null, (string) $name);
            }

            return $entries;
        }

        $entries = [];
        foreach ($this->locales() as $locale) {
            foreach ($providers as $section => $provider) {
                $entries[$locale.'-'.$section] = $this->makeProvider($provider, $locale, (string) $section);
            }
        }

        return $entries;
    }

    /** @return list<string> */
    protected function locales(): array
    {
        $locales = config('crud.sitemap.locales');
        if ($locales === null) {
            $locales = config('uikittemplate.availableLanguages', [config('app.locale', 'en')]);
        }

        if (! is_array($locales)) {
            return [];
        }

        $codes = array_is_list($locales) ? $locales : array_keys($locales);

        return array_values(array_filter($codes, fn ($locale) => is_string($locale) && $locale !== ''));
    }

    protected function makeProvider(mixed $provider, ?string $locale, string $section): SitemapProvider
    {
        if ($provider instanceof SitemapProvider) {
            return $provider;
        }

        if (is_array($provider)) {
            return new ConfiguredSitemapProvider($section, $locale, $provider);
        }

        if (is_callable($provider)) {
            $resolved = $provider($locale, $section);
            if (! $resolved instanceof SitemapProvider) {
                throw new \InvalidArgumentException('Sitemap provider factory must return a SitemapProvider.');
            }

            return $resolved;
        }

        if (is_string($provider) && class_exists($provider)) {
            $parameters = ['section' => $section];
            if ($locale !== null) {
                $parameters['locale'] = $locale;
            }
            $resolved = ($this->container ?? app())->make($provider, $parameters);
            if (! $resolved instanceof SitemapProvider) {
                throw new \InvalidArgumentException('Sitemap provider class must implement SitemapProvider.');
            }

            return $resolved;
        }

        throw new \InvalidArgumentException('Invalid sitemap provider for section '.$section);
    }

    private function cleanupPrevious(string $path): void
    {
        $directory = dirname($path);
        foreach (glob($directory.DIRECTORY_SEPARATOR.'sitemap-*.xml') ?: [] as $file) {
            @unlink($file);
        }
    }

    private function atomicPut(string $path, string $contents): void
    {
        $temporary = $path.'.tmp';
        if (file_put_contents($temporary, $contents) === false) {
            throw new \RuntimeException('Unable to write sitemap temporary file: '.$temporary);
        }
        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to publish sitemap file: '.$path);
        }
    }
}
