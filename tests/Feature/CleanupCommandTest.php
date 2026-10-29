<?php

namespace IlBronza\CRUD\Tests\Feature;

use IlBronza\CRUD\Commands\CleanupCommand;
use IlBronza\CRUD\Tests\TestCase;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use Mockery;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class CleanupCommandTest extends TestCase
{
    protected $cleanupRoot;

    /**
     * Isolate every destructive operation from the host and Testbench application files.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupRoot = realpath(sys_get_temp_dir()) . '/crud-cleanup-' . bin2hex(random_bytes(8));
        File::makeDirectory($this->cleanupRoot . '/storage/framework/views', 0755, true);
        File::makeDirectory($this->cleanupRoot . '/bootstrap/cache', 0755, true);
        $this->app->useStoragePath($this->cleanupRoot . '/storage');
        $this->app->useBootstrapPath($this->cleanupRoot . '/bootstrap');
        $this->app['config']->set('view.compiled', storage_path('framework/views'));
        $this->app['config']->set('cache.default', 'array');
        $this->app['cache']->forgetDriver();
    }

    protected function tearDown(): void
    {
        if ($this->cleanupRoot) {
            File::deleteDirectory($this->cleanupRoot);
        }

        parent::tearDown();
    }

    public function test_default_cleanup_removes_caches_and_temporary_files_but_preserves_application_data(): void
    {
        $temporary = [
            'storage/debugbar/request.json',
            'storage/app/backup-temp/temp/nested/dump.sql',
            'storage/framework/cache/data/aa/bb/cache-entry',
            'storage/framework/cache/facade-test.php',
            'storage/framework/views/compiled.php',
        ];
        $protected = [
            'storage/debugbar/.gitignore',
            'storage/app/backup-temp/temp/.gitkeep',
            'storage/framework/cache/data/.gitignore',
            'storage/framework/views/.gitignore',
            'storage/framework/sessions/active-session',
            'storage/logs/laravel.log',
            'storage/app/backups/final.zip',
            'storage/app/backup-temp/final.zip',
            'storage/app/public/upload.jpg',
            'storage/app/media/photo.jpg',
            'database/database.sqlite',
            'vendor/package/file.php',
            'public/image.png',
            'config/app.php',
            '.env',
        ];

        foreach (array_merge($temporary, $protected) as $path) {
            $this->putFile($path);
        }

        $this->app['cache']->put('cleanup-test', 'cached', 60);
        $tester = $this->runCleanup();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Skipping debugbar:clear: command not installed.', $tester->getDisplay());
        $this->assertStringContainsString('Cleanup completed.', $tester->getDisplay());
        $this->assertNull($this->app['cache']->get('cleanup-test'));

        foreach ($temporary as $path) {
            $this->assertFileDoesNotExist($this->cleanupRoot . '/' . $path);
        }

        foreach ($protected as $path) {
            $this->assertFileExists($this->cleanupRoot . '/' . $path);
        }

        $this->assertDirectoryExists(storage_path('app/backup-temp/temp'));
        $this->assertDirectoryExists(storage_path('framework/views'));
    }

    public function test_all_removes_sessions_and_rotated_logs_but_keeps_structural_and_unrelated_files(): void
    {
        foreach ([
            'storage/framework/sessions/session',
            'storage/logs/laravel.log',
            'storage/logs/archive/laravel.log.1.gz',
            'storage/logs/laravel-2026-09-22.log',
            'storage/logs/.gitignore',
            'storage/logs/README.md',
            'storage/logs/web.config',
            'storage/app/backups/final.zip',
            'storage/app/public/upload.jpg',
        ] as $path) {
            $this->putFile($path);
        }

        $tester = $this->runCleanup(['--all' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertFileDoesNotExist(storage_path('framework/sessions/session'));
        $this->assertFileDoesNotExist(storage_path('logs/laravel.log'));
        $this->assertFileDoesNotExist(storage_path('logs/archive/laravel.log.1.gz'));
        $this->assertFileDoesNotExist(storage_path('logs/laravel-2026-09-22.log'));

        foreach (['logs/.gitignore', 'logs/README.md', 'logs/web.config', 'app/backups/final.zip', 'app/public/upload.jpg'] as $path) {
            $this->assertFileExists(storage_path($path));
        }
    }

    public function test_sessions_and_logs_can_be_enabled_independently(): void
    {
        $this->putFile('storage/framework/sessions/session');
        $this->putFile('storage/logs/laravel.log');

        $this->assertSame(0, $this->runCleanup(['--sessions' => true])->getStatusCode());
        $this->assertFileDoesNotExist(storage_path('framework/sessions/session'));
        $this->assertFileExists(storage_path('logs/laravel.log'));

        $this->putFile('storage/framework/sessions/session');

        $this->assertSame(0, $this->runCleanup(['--logs' => true])->getStatusCode());
        $this->assertFileExists(storage_path('framework/sessions/session'));
        $this->assertFileDoesNotExist(storage_path('logs/laravel.log'));
    }

    public function test_file_cache_preserves_nested_placeholders_and_counts_each_removed_byte_once(): void
    {
        $this->app['config']->set('cache.default', 'file');
        $this->app['config']->set('cache.stores.file.path', storage_path('framework/cache/data'));
        $this->app['cache']->forgetDriver();
        $this->putFile('storage/framework/cache/data/aa/bb/entry', str_repeat('x', 1024));
        $this->putFile('storage/framework/cache/data/aa/.gitignore');
        $this->putFile('storage/debugbar/request.json', str_repeat('x', 1024));
        $this->putFile('storage/framework/views/compiled.php', str_repeat('x', 1024));

        $tester = $this->runCleanup();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertFileExists(storage_path('framework/cache/data/aa/.gitignore'));
        $this->assertFileDoesNotExist(storage_path('framework/cache/data/aa/bb/entry'));
        $this->assertStringContainsString('Freed approximately 3.0 KiB', $tester->getDisplay());
    }

    public function test_missing_directories_and_missing_artisan_commands_are_harmless(): void
    {
        $this->app['config']->set('cache.default', 'file');
        $this->app['config']->set('cache.stores.file.path', storage_path('framework/cache/data'));
        $this->app['cache']->forgetDriver();
        File::deleteDirectory(storage_path());

        $console = new ConsoleApplication($this->app, $this->app['events'], $this->app->version());
        $command = new CleanupCommand();
        $console->add($command);
        $tester = new CommandTester($command);
        $tester->execute(['--all' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Skipping config:clear: command not installed.', $tester->getDisplay());
        $this->assertStringContainsString('Skipping missing directory:', $tester->getDisplay());
        $this->assertStringContainsString('Freed approximately 0 B', $tester->getDisplay());
    }

    public function test_installed_debugbar_command_is_executed_before_direct_fallback(): void
    {
        $this->putFile('storage/debugbar/command.json', str_repeat('x', 1024));
        $this->putFile('storage/debugbar/fallback.json', str_repeat('x', 1024));
        $debugbar = new class extends Command {
            protected $signature = 'debugbar:clear';
            public $called = false;

            public function handle(): int
            {
                $this->called = true;
                File::delete(storage_path('debugbar/command.json'));

                return 0;
            }
        };
        $this->app[Kernel::class]->registerCommand($debugbar);

        $tester = $this->runCleanup();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertTrue($debugbar->called);
        $this->assertFileDoesNotExist(storage_path('debugbar/fallback.json'));
        $this->assertStringContainsString('Freed approximately 2.0 KiB', $tester->getDisplay());
    }

    public function test_failed_artisan_commands_do_not_prevent_remaining_cleanup(): void
    {
        $this->putFile('storage/app/backup-temp/temp/dump.sql');
        $this->app[Kernel::class]->registerCommand(new class extends Command {
            protected $signature = 'config:clear';

            public function handle(): int
            {
                throw new RuntimeException('Simulated permission denied.');
            }
        });
        $this->app[Kernel::class]->registerCommand(new class extends Command {
            protected $signature = 'debugbar:clear';

            public function handle(): int
            {
                return 1;
            }
        });

        $tester = $this->runCleanup();

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Simulated permission denied.', $tester->getDisplay());
        $this->assertStringContainsString('Artisan returned a non-zero exit code.', $tester->getDisplay());
        $this->assertStringContainsString('sudo -u www-data php artisan ib:cleanup', $tester->getDisplay());
        $this->assertFileDoesNotExist(storage_path('app/backup-temp/temp/dump.sql'));
    }

    public function test_permission_denied_for_one_file_does_not_prevent_sibling_deletion(): void
    {
        $this->putFile('storage/debugbar/locked.json');
        $this->putFile('storage/debugbar/removable.json');
        File::partialMock()->shouldReceive('delete')->with(storage_path('debugbar/locked.json'))->andReturn(false);
        File::shouldReceive('delete')->with(Mockery::on(function ($path) {
            return $path !== storage_path('debugbar/locked.json');
        }))->passthru();

        $tester = $this->runCleanup();

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertFileExists(storage_path('debugbar/locked.json'));
        $this->assertFileDoesNotExist(storage_path('debugbar/removable.json'));
        $this->assertStringContainsString('permission denied or file is in use', $tester->getDisplay());
        $this->assertStringNotContainsString('ErrorException', $tester->getDisplay());
    }

    public function test_linked_roots_and_linked_children_do_not_delete_their_targets(): void
    {
        $this->putFile('storage/app/public/upload.jpg');
        $this->putFile('storage/framework/cache/entry');
        symlink(storage_path('app/public'), storage_path('debugbar'));
        symlink(storage_path('app/public'), storage_path('framework/cache/uploads'));

        $tester = $this->runCleanup(['--all' => true]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertFileExists(storage_path('app/public/upload.jpg'));
        $this->assertFileDoesNotExist(storage_path('framework/cache/entry'));
        $this->assertTrue(is_link(storage_path('framework/cache/uploads')));
        $this->assertStringContainsString('Refusing cleanup through symbolic link', $tester->getDisplay());
    }

    public function test_custom_file_cache_and_compiled_view_paths_are_cleaned(): void
    {
        $this->app['config']->set('cache.default', 'file');
        $this->app['config']->set('cache.stores.file.path', $this->cleanupRoot . '/custom-cache');
        $this->app['config']->set('view.compiled', $this->cleanupRoot . '/custom-views');
        $this->app['cache']->forgetDriver();
        $this->app['cache']->put('cleanup-test', 'cached', 60);
        $this->putFile('custom-views/compiled.php');
        $this->putFile('custom-views/.gitignore');

        $tester = $this->runCleanup();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertNull($this->app['cache']->get('cleanup-test'));
        $this->assertFileDoesNotExist($this->cleanupRoot . '/custom-views/compiled.php');
        $this->assertFileExists($this->cleanupRoot . '/custom-views/.gitignore');
    }

    public function test_silent_bootstrap_deletion_failures_are_reported(): void
    {
        $this->app[Kernel::class]->all();
        $path = $this->app->getCachedConfigPath();
        File::put($path, '<?php return [];');
        $this->putFile('storage/app/backup-temp/temp/dump.sql');
        File::partialMock()->shouldReceive('delete')->with($path)->andReturn(false);
        File::shouldReceive('delete')->with(Mockery::on(function ($candidate) use ($path) {
            return $candidate !== $path;
        }))->passthru();

        // The command may already hold the original filesystem instance.
        $this->app[Kernel::class]->registerCommand(new \Illuminate\Foundation\Console\ConfigClearCommand(File::getFacadeRoot()));
        $tester = $this->runCleanup();

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Cache file still exists', $tester->getDisplay());
        $this->assertFileExists($path);
        $this->assertFileDoesNotExist(storage_path('app/backup-temp/temp/dump.sql'));
    }

    public function test_failed_cache_flush_is_reported_without_stopping_cleanup(): void
    {
        $store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $store->shouldReceive('flush')->once()->andReturn(false);
        $this->app['cache']->extend('failed-cleanup-cache', function () use ($store) {
            return $this->app['cache']->repository($store);
        });
        $this->app['config']->set('cache.default', 'failed-cleanup-cache');
        $this->app['config']->set('cache.stores.failed-cleanup-cache.driver', 'failed-cleanup-cache');
        $this->putFile('storage/app/backup-temp/temp/dump.sql');

        $tester = $this->runCleanup();

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('The cache store could not be cleared.', $tester->getDisplay());
        $this->assertFileDoesNotExist(storage_path('app/backup-temp/temp/dump.sql'));
    }

    public function test_bootstrap_cleanup_only_removes_laravel_managed_files(): void
    {
        $paths = [
            $this->app->getCachedConfigPath(),
            $this->app->getCachedRoutesPath(),
            $this->app->getCachedEventsPath(),
            $this->app->getCachedServicesPath(),
            $this->app->getCachedPackagesPath(),
        ];

        // Resolve commands before planting synthetic cache files that Laravel must not load.
        $this->app[Kernel::class]->all();

        foreach ($paths as $path) {
            File::put($path, '<?php return [];');
        }

        $this->putFile('bootstrap/cache/.gitignore');
        $this->putFile('bootstrap/cache/keep.txt');

        $tester = $this->runCleanup();

        $this->assertSame(0, $tester->getStatusCode());

        foreach ($paths as $path) {
            $this->assertFileDoesNotExist($path);
        }

        $this->assertFileExists($this->cleanupRoot . '/bootstrap/cache/.gitignore');
        $this->assertFileExists($this->cleanupRoot . '/bootstrap/cache/keep.txt');
    }

    /**
     * Exercise the command registered by the package service provider.
     */
    protected function runCleanup(array $options = []): CommandTester
    {
        $commands = $this->app[Kernel::class]->all();
        $this->assertArrayHasKey('ib:cleanup', $commands);
        $tester = new CommandTester($commands['ib:cleanup']);
        $tester->execute($options);

        return $tester;
    }

    /**
     * Create a fixture within this test's isolated application directory.
     */
    protected function putFile(string $path, string $contents = 'keep me'): void
    {
        $path = $this->cleanupRoot . '/' . $path;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }
}
