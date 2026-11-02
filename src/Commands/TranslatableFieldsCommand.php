<?php

namespace IlBronza\CRUD\Commands;

use IlBronza\CRUD\Helpers\Forms\TranslatableFormFields;
use IlBronza\CRUD\Helpers\ModelHelpers\TranslatableColumnDefinition;
use IlBronza\CRUD\Helpers\ModelHelpers\TranslatableModelFinder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use RuntimeException;
use Throwable;

class TranslatableFieldsCommand extends Command
{
    protected $signature = 'crud:translations
        {model? : Model class, App model name or morph alias; prompts when omitted}
        {--check : Report missing columns without generating a migration}
        {--generate : Generate a migration for missing translated columns}';

    protected $description = 'Check TranslatableField columns and generate a migration matching the base fields';

    public function handle(): int
    {
        try {
            if ($this->option('check') && $this->option('generate')) {
                throw new RuntimeException('Use either --check or --generate.');
            }

            $class = $this->resolveModel();

            if (! is_subclass_of($class, Model::class) || ! (new ReflectionClass($class))->isInstantiable()) {
                throw new RuntimeException("[{$class}] is not an instantiable Eloquent model.");
            }

            $model = new $class;
            $fields = TranslatableModelFinder::fields($model);

            if ($fields === []) {
                throw new RuntimeException("Model [{$class}] has no TranslatableField casts.");
            }

            $schema = $model->getConnection()->getSchemaBuilder();
            $table = $model->getTable();

            if (! $schema->hasTable($table)) {
                throw new RuntimeException("Table [{$table}] does not exist on the model connection.");
            }

            $columns = array_column($schema->getColumns($table), null, 'name');
            $locales = array_diff(array_keys(TranslatableFormFields::locales()), [config('app.base_locale', 'it')]);
            $missing = [];

            foreach ($fields as $field) {
                if (! isset($columns[$field])) {
                    throw new RuntimeException("Base column [{$table}.{$field}] does not exist. Create it first.");
                }

                foreach ($locales as $locale) {
                    $name = $locale.'_'.$field;

                    if (! isset($columns[$name])) {
                        $missing[$name] = $columns[$field];
                    }
                }
            }

            $this->info("Model: {$class}");
            $this->line("Table: {$table}; connection: ".$model->getConnection()->getName());

            if ($missing === []) {
                $this->info('All translated columns already exist.');

                return self::SUCCESS;
            }

            $this->table(['Missing column', 'Base column', 'Type'], array_map(
                static fn ($name, $column) => [$name, $column['name'], $column['type']],
                array_keys($missing), array_values($missing)
            ));

            if (! $this->option('generate')) {
                $this->line('Use --generate to create the migration.');

                return self::FAILURE;
            }

            $this->generateMigration($model, $missing);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function resolveModel(): string
    {
        if ($class = $this->argument('model')) {
            $class = Relation::getMorphedModel($class) ?? ltrim($class, '\\');

            if (! class_exists($class)) {
                $class = $this->laravel->getNamespace().'Models\\'.$class;
            }

            return $class;
        }

        $finder = new TranslatableModelFinder;
        $models = $finder->find();

        foreach ($finder->errors as $class => $message) {
            $this->warn("Skipping [{$class}]: {$message}");
        }

        if ($models === []) {
            throw new RuntimeException('No models with TranslatableField casts found. Specify the full model class.');
        }

        if (! $this->input->isInteractive()) {
            foreach ($models as $model) {
                $this->line('  - '.$model);
            }

            throw new RuntimeException('Specify a model when running non-interactively.');
        }

        return $this->choice('Which model should be checked?', $models);
    }

    protected function generateMigration(Model $model, array $missing): void
    {
        $definitions = [];

        foreach ($missing as $name => $column) {
            $definitions[] = '            '.TranslatableColumnDefinition::make($column, $name, $model->getConnection()->getDriverName());
        }

        $table = $model->getTable();
        $connection = $model->getConnectionName();
        $identity = substr(hash('sha256', serialize([$connection, $table, $missing])), 0, 12);
        $suffix = '_add_translatable_columns_to_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $table).'_'.$identity.'_table.php';
        $directory = database_path('migrations');

        // Compare generated manifests without executing pending migration PHP files.
        foreach (glob($directory.'/*_add_translatable_columns_to_*_table.php') ?: [] as $existing) {
            if (! preg_match('/^\/\/ crud:translations (.+)$/m', File::get($existing), $match)) {
                continue;
            }

            $metadata = json_decode($match[1], true);

            if (($metadata['connection'] ?? null) === $model->getConnection()->getName()
                && ($metadata['table'] ?? null) === $table
                && array_intersect($metadata['columns'] ?? [], array_keys($missing))) {
                throw new RuntimeException('A migration for some missing columns already exists: '.basename($existing).'. Apply or review it before generating another.');
            }
        }

        $content = strtr(File::get(__DIR__.'/Stubs/translatable_columns_migration.stub'), [
            '{{ connection }}' => var_export($connection, true),
            '{{ table }}' => var_export($table, true),
            '{{ columns }}' => implode("\n", $definitions),
            '{{ column_names }}' => '['.implode(', ', array_map(static fn ($name) => var_export($name, true), array_keys($missing))).']',
            '{{ metadata }}' => json_encode([
                'connection' => $model->getConnection()->getName(),
                'table' => $table,
                'columns' => array_keys($missing),
            ], JSON_THROW_ON_ERROR),
        ]);

        File::ensureDirectoryExists($directory);
        $path = $directory.'/'.date('Y_m_d_His').$suffix;

        if (File::put($path, $content) === false) {
            throw new RuntimeException("Could not write migration [{$path}].");
        }

        $this->info("Migration created: {$path}");
        $this->line('Review the migration, then run php artisan migrate.');
    }
}
