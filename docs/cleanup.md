# Application cleanup

The package registers `ib:cleanup` through `CRUDServiceProvider` when Laravel is
running in console mode. No additional provider or configuration is needed.

```bash
php artisan ib:cleanup
php artisan ib:cleanup --sessions
php artisan ib:cleanup --logs
php artisan ib:cleanup --all
```

The signature is:

```php
ib:cleanup
    {--all : Clean all optional temporary data}
    {--sessions : Remove stored file sessions}
    {--logs : Remove Laravel log files}
```

## Default operations

The command performs the core `optimize:clear` operations separately so an error
cannot prevent the remaining tasks from running:

| Operation | Scope |
| --- | --- |
| Application cache | Flush the configured default cache store. For Laravel's file store, selectively clean its configured directory, preserving dotfiles and symbolic links. |
| `clear-compiled` | Laravel's compiled services/packages manifests and legacy compiled file, where supported. |
| `config:clear` | Laravel's cached configuration file. |
| `event:clear` | Laravel's cached event file. |
| `route:clear` | Laravel's cached route file. |
| `view:clear` | The configured `view.compiled` path, with direct cleanup for a custom path. |
| `debugbar:clear` | Run only if registered; Debugbar's configured storage is handled by its command. |
| Direct directory cleanup | `storage/debugbar`, `storage/app/backup-temp/temp`, `storage/framework/cache`, `storage/framework/views`. |

Bootstrap cleanup uses Laravel's cache-path methods; it does not empty the entire
`bootstrap/cache` directory. Package-specific `optimize:clear` hooks are not run.
The cache flush uses the store API rather than `cache:clear`, so it does not emit
that command's `cache:clearing` and `cache:cleared` events. Real-time facade files
are removed by direct cleanup of `storage/framework/cache`.

## Optional operations and preservation

- `--sessions` empties `storage/framework/sessions`.
- `--logs` removes `*.log` and rotated `*.log.*` files under `storage/logs`, including
  nested directories. Other filenames, such as `README.md` and `web.config`, stay.
- `--all` enables both options, in addition to all default operations.

Direct cleanup preserves directory roots, dotfiles (including `.gitignore`,
`.gitkeep` and `.htaccess`), hidden directories, and symbolic links. Empty ordinary
subdirectories are removed. Roots or ancestors that are symbolic links are rejected
with a readable error; resolve the application paths to real directories before
running cleanup in such a deployment.

Uploads, media, final backups, database files, `vendor`, `public`, configuration
sources, and `.env` are outside the direct cleanup paths. Only the exact
`storage/app/backup-temp/temp` directory is cleaned for backups; sibling files and
backup destinations are retained. Avoid running cleanup during an active backup:
intermediate files in this directory may belong to the running process.

Laravel and Debugbar commands use the host application's configured paths and
implementations. These paths must actually point to disposable cache data.

## Results and compatibility

Missing commands and directories are skipped. Deletion failures are reported with
the affected path; remaining entries and operations are still attempted. Exit code
`0` means cleanup completed without detected errors, and `1` means an operation
failed. Permission failures include an example invocation as the PHP-FPM user.

Byte totals are approximate differences in local regular-file sizes before and
after each operation. They exclude remote cache storage, filesystem overhead,
symbolic-link targets and unreadable files. Overlapping operations are measured
sequentially so removed files are not counted twice.

Only the default application cache store is flushed. As with `cache:clear`, a
shared Redis or other cache backend may contain data used by other applications
or cache-backed sessions; use a dedicated cache backend where isolation is needed.
The session option only affects the standard file-session directory, and the log
option only affects standard local log filenames; remote/custom session and log
storage is not purged.

The package does not declare a PHP or Laravel version constraint in `composer.json`.
The command uses established Laravel/PHP APIs, checks command availability and
guards version-specific cache-path methods. Its feature tests run against the
shared `.ibtest` environment (Laravel 11.51, PHP 8.5.8, PHPUnit 11.5); other Laravel
versions have not been verified.
