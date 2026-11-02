<?php

namespace IlBronza\CRUD\Helpers\ModelHelpers;

use Composer\Autoload\ClassLoader;
use IlBronza\CRUD\Models\Casts\TranslatableField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Throwable;

final class TranslatableModelFinder
{
    public array $errors = [];

    public static function fields(Model $model): array
    {
        return array_keys(array_filter($model->getCasts(), static fn ($cast) =>
            is_string($cast) && ltrim(explode(':', $cast, 2)[0], '\\') === TranslatableField::class
        ));
    }

    /** Scan application models and the effective package models registered in config. */
    public function find(): array
    {
        $classes = array_merge(config('crud.translatableFields.models', []), array_values(Relation::morphMap()));

        foreach (config()->all() as $configuration) {
            $configuredModels = is_array($configuration) ? ($configuration['models'] ?? []) : [];

            foreach (is_array($configuredModels) ? $configuredModels : [] as $model) {
                if (is_array($model) && is_string($model['class'] ?? null)) {
                    $classes[] = $model['class'];
                }
            }
        }

        foreach (spl_autoload_functions() as $autoload) {
            $loader = is_array($autoload) ? $autoload[0] : null;

            if (! $loader instanceof ClassLoader) {
                continue;
            }

            foreach ($loader->getPrefixesPsr4() as $namespace => $paths) {
                if (! str_starts_with($namespace, app()->getNamespace())) {
                    continue;
                }

                if (str_contains($namespace, '\\Tests\\')) {
                    continue;
                }

                foreach ($paths as $path) {
                    $modelsNamespace = str_contains($namespace, '\\Models\\') ? $namespace : $namespace.'Models\\';
                    $directory = str_contains($namespace, '\\Models\\') ? $path : $path.'/Models';

                    if (! is_dir($directory)) {
                        continue;
                    }

                    foreach (File::allFiles($directory) as $file) {
                        if ($file->getExtension() === 'php') {
                            $classes[] = $modelsNamespace.str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
                        }
                    }
                }
            }

            foreach (array_keys($loader->getClassMap()) as $class) {
                if (str_contains($class, '\\Models\\') && str_starts_with($class, app()->getNamespace())) {
                    $classes[] = $class;
                }
            }
        }

        $models = [];
        $this->errors = [];

        foreach (array_unique($classes) as $class) {
            try {
                if (is_subclass_of($class, Model::class) && (new ReflectionClass($class))->isInstantiable()
                    && self::fields(new $class) !== []) {
                    $models[] = $class;
                }
            } catch (Throwable $exception) {
                $this->errors[$class] = $exception->getMessage();
            }
        }

        sort($models);

        return $models;
    }
}
