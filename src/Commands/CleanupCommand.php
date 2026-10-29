<?php

namespace IlBronza\CRUD\Commands;

use FilesystemIterator;
use Illuminate\Cache\FileStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class CleanupCommand extends Command
{
    /**
     * The console command signature and explicitly enabled destructive options.
     *
     * @var string
     */
    protected $signature = 'ib:cleanup
        {--all : Clean all optional temporary data}
        {--sessions : Remove stored file sessions}
        {--logs : Remove Laravel log files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Laravel caches and regenerable temporary files safely';

    /** @var int Approximate bytes removed from local regular files. */
    protected $freedBytes = 0;

    /** @var bool Whether any cleanup operation failed. */
    protected $failed = false;

    /**
     * Clean the supported caches and temporary directories, keeping optional data by default.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->freedBytes = 0;
        $this->failed = false;

        $this->clearLaravelCaches();
        $debugbarPath = $this->laravel['config']->get('debugbar.storage.path');
        $this->runCleanupCommand('debugbar:clear', [
            is_string($debugbarPath) && $debugbarPath !== '' ? $debugbarPath : storage_path('debugbar'),
        ]);

        foreach ([
            'debugbar' => 'Debugbar files',
            'app/backup-temp/temp' => 'backup temporary files',
            'framework/cache' => 'framework cache files',
            'framework/views' => 'compiled views',
        ] as $path => $label) {
            $this->cleanDirectory(storage_path($path), $label);
        }

        if ($this->option('all') || $this->option('sessions')) {
            $this->cleanDirectory(storage_path('framework/sessions'), 'file sessions');
        }

        if ($this->option('all') || $this->option('logs')) {
            $this->cleanDirectory(storage_path('logs'), 'Laravel log files', true);
        }

        $this->newLine();

        if ($this->failed) {
            $this->warn('Cleanup completed with errors. Other operations were still attempted.');
            $this->line('For permission errors, run with the same user used by PHP-FPM, for example:');
            $options = $this->option('all') ? ' --all' :
                (($this->option('sessions') ? ' --sessions' : '') . ($this->option('logs') ? ' --logs' : ''));
            $this->line('sudo -u www-data php artisan ib:cleanup' . $options);
        } else {
            $this->info('Cleanup completed.');
        }

        $this->info('Freed approximately ' . $this->formatBytes($this->freedBytes) . ' (local files only).');

        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Run the core optimize:clear tasks separately so one failure cannot stop the others.
     * File caches use selective deletion to preserve nested placeholders and symbolic links.
     *
     * @return void
     */
    protected function clearLaravelCaches(): void
    {
        $this->info('Clearing Laravel caches...');

        try {
            $store = $this->laravel['cache']->store()->getStore();

            if ($store instanceof FileStore) {
                $this->cleanDirectory($store->getDirectory(), 'application file cache');
            } else {
                $this->measureOperation('application cache', [], function () use ($store) {
                    if ($store->flush() === false) {
                        throw new RuntimeException('The cache store could not be cleared.');
                    }
                });
            }
        } catch (Throwable $exception) {
            $this->reportFailure('application cache', $exception->getMessage());
        }

        foreach ([
            'clear-compiled' => ['getCachedServicesPath', 'getCachedPackagesPath', 'getCachedCompilePath'],
            'config:clear' => ['getCachedConfigPath'],
            'event:clear' => ['getCachedEventsPath'],
            'route:clear' => ['getCachedRoutesPath'],
        ] as $command => $methods) {
            $paths = [];

            foreach ($methods as $method) {
                if (method_exists($this->laravel, $method)) {
                    $paths[] = $this->laravel->{$method}();
                }
            }

            $this->runCleanupCommand($command, $paths, true);
        }

        $views = $this->laravel['config']->get('view.compiled');

        if (is_string($views) && $views !== '') {
            $this->runCleanupCommand('view:clear', [$views]);

            if ($views !== storage_path('framework/views')) {
                $this->cleanDirectory($views, 'configured compiled views');
            }
        } else {
            $this->line('Skipping view:clear: no compiled view path configured.');
        }
    }

    /**
     * Run an installed Artisan command, measure its local files and report failures readably.
     * Never delegate deletion through a symbolic link; direct cleanup remains available.
     *
     * @param string $command
     * @param array $paths
     * @param bool $verifyRemoval Whether the paths are individual cache files that must disappear.
     * @return void
     */
    protected function runCleanupCommand(string $command, array $paths, bool $verifyRemoval = false): void
    {
        $this->measureOperation($command, $paths, function () use ($command, $paths, $verifyRemoval) {
            if (! $this->getApplication()->has($command)) {
                $this->line("Skipping {$command}: command not installed.");

                return;
            }

            foreach ($paths as $path) {
                $this->assertNoLinkedAncestors($path);
            }

            if ($this->call($command) !== 0) {
                throw new RuntimeException('Artisan returned a non-zero exit code.');
            }

            if ($verifyRemoval) {
                foreach ($paths as $path) {
                    clearstatcache(true, $path);

                    if (File::exists($path)) {
                        $this->reportFailure($path, 'Cache file still exists; check file and directory permissions.');
                    }
                }
            }
        });
    }

    /**
     * Empty an allowed temporary directory without removing its root or structural files.
     * Log cleanup only removes *.log and rotated *.log.* files.
     *
     * @param string $path
     * @param string $label
     * @param bool $logsOnly
     * @return void
     */
    protected function cleanDirectory(string $path, string $label, bool $logsOnly = false): void
    {
        $this->measureOperation($label, [$path], function () use ($path, $logsOnly) {
            $this->assertNoLinkedAncestors($path);

            if (! File::exists($path)) {
                $this->line('Skipping missing directory: ' . OutputFormatter::escape($path));

                return;
            }

            $this->cleanContents($path, $logsOnly);
        });
    }

    /**
     * Delete entries individually so a denied file does not prevent cleanup of its siblings.
     * Preserve dotfiles and links; remove empty subdirectories only after inspecting them.
     *
     * @param string $path
     * @param bool $logsOnly
     * @return void
     */
    protected function cleanContents(string $path, bool $logsOnly): void
    {
        foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $entry) {
            $entryPath = $entry->getPathname();

            try {
                if ($entry->isLink() || strpos($entry->getFilename(), '.') === 0) {
                    continue;
                }

                if ($entry->isDir()) {
                    $this->cleanContents($entryPath, $logsOnly);

                    if (! (new FilesystemIterator($entryPath))->valid() && ! @rmdir($entryPath)) {
                        throw new RuntimeException('Unable to remove empty directory; check permissions.');
                    }
                } elseif ($entry->isFile() && (! $logsOnly || preg_match('/\.log(?:\..+)?$/i', $entry->getFilename()))) {
                    if (! File::delete($entryPath)) {
                        throw new RuntimeException('Unable to remove file; permission denied or file is in use.');
                    }
                }
            } catch (Throwable $exception) {
                $this->reportFailure($entryPath, $exception->getMessage());
            }
        }
    }

    /**
     * Reject linked roots and ancestors to avoid deleting data outside an allowed directory.
     *
     * @param string $path
     * @return void
     */
    protected function assertNoLinkedAncestors(string $path): void
    {
        for ($current = $path; $current !== dirname($current); $current = dirname($current)) {
            if (is_link($current)) {
                throw new RuntimeException('Refusing cleanup through symbolic link: ' . $current);
            }
        }
    }

    /**
     * Measure a single operation before and after deletion without counting bytes twice.
     *
     * @param string $label
     * @param array $paths
     * @param callable $operation
     * @return void
     */
    protected function measureOperation(string $label, array $paths, callable $operation): void
    {
        $this->line('Cleaning ' . $label . '...');
        $before = $this->localSize($paths);

        try {
            $operation();
        } catch (Throwable $exception) {
            $this->reportFailure($label, $exception->getMessage());
        }

        $removed = max(0, $before - $this->localSize($paths));
        $this->freedBytes += $removed;
        $this->line($this->formatBytes($removed) . ' removed.');
    }

    /**
     * Estimate regular-file sizes without following links or failing on unreadable paths.
     *
     * @param array $paths
     * @return int
     */
    protected function localSize(array $paths): int
    {
        $bytes = 0;

        foreach ($paths as $path) {
            try {
                $this->assertNoLinkedAncestors($path);
                clearstatcache(true, $path);

                if (is_file($path)) {
                    $bytes += File::size($path);
                } elseif (is_dir($path)) {
                    foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $entry) {
                        $bytes += $this->localSize([$entry->getPathname()]);
                    }
                }
            } catch (Throwable $exception) {
                // Unreadable entries cannot contribute to the approximate byte count.
            }
        }

        return $bytes;
    }

    /**
     * Record a recoverable failure without exposing a raw exception or a stack trace.
     *
     * @param string $path
     * @param string $reason
     * @return void
     */
    protected function reportFailure(string $path, string $reason): void
    {
        $this->failed = true;
        $this->error(OutputFormatter::escape('Unable to clean ' . $path . ': ' . $reason));
    }

    /**
     * Format an approximate byte count using binary units.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
        $unit = 0;

        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }

        return number_format($bytes, $unit === 0 ? 0 : 1, '.', '') . ' ' . $units[$unit];
    }
}
