<?php

namespace IlBronza\CRUD\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeArchiveMigrationCommand extends Command
{
    protected $signature = 'crud:archive-migration {model : The fully qualified model class}';

    protected $description = 'Create a migration that adds the archived column (string, max 12 chars) for a model';

    public function handle(): int
    {
        $modelClass = $this->argument('model');

        if (! class_exists($modelClass)) {
            $this->error("Model class [{$modelClass}] does not exist.");

            return self::FAILURE;
        }

        $table = (new $modelClass)->getTable();
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_add_archived_to_{$table}_table.php";
        $path = database_path('migrations/' . $fileName);

        if (file_exists($path)) {
            $this->error("Migration already exists: {$fileName}");

            return self::FAILURE;
        }

        $stub = file_get_contents(__DIR__ . '/Stubs/archive_migration.stub');

        $content = str_replace(
            ['{{ table }}', '{{ class }}'],
            [$table, 'AddArchivedTo' . Str::studly($table) . 'Table'],
            $stub
        );

        if (file_put_contents($path, $content) === false) {
            $this->error('Could not write migration file.');

            return self::FAILURE;
        }

        $this->info('Migration created successfully.');
        $this->line("  <fg=gray>{$path}</>");

        return self::SUCCESS;
    }
}
