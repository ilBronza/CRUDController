<?php

namespace IlBronza\CRUD\Commands;

use IlBronza\CRUD\Sitemap\SitemapWriter;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate {--path= : Absolute path for sitemap.xml (defaults to config or public/sitemap.xml)}';

    protected $description = 'Generate the public XML sitemap from configured CRUD sitemap providers';

    public function handle(SitemapWriter $writer): int
    {
        $path = $this->option('path') ?: null;
        $written = $writer->write($path);

        foreach ($written as $file) {
            $this->info('Wrote '.$file);
        }

        return self::SUCCESS;
    }
}
