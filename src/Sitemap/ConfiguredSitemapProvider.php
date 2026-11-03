<?php

namespace IlBronza\CRUD\Sitemap;

use IlBronza\CRUD\Enums\SitemapChangeFrequency;
use IlBronza\CRUD\Interfaces\SitemapProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ConfiguredSitemapProvider implements SitemapProvider
{
    public function __construct(
        private readonly string $section,
        private readonly ?string $locale,
        private readonly array $config,
    ) {}

    public function items(): iterable
    {
        $driver = $this->config['driver'] ?? null;

        yield from match ($driver) {
            'routes' => $this->routeItems(),
            'eloquent' => $this->eloquentItems($this->config),
            default => throw new \InvalidArgumentException(
                "Unknown sitemap driver [{$driver}] for section [{$this->section}]."
            ),
        };

        foreach ($this->config['extra'] ?? [] as $extra) {
            if (! is_array($extra)) {
                continue;
            }
            yield from $this->eloquentItems(array_merge(
                ['changefreq' => $this->config['changefreq'] ?? null, 'priority' => $this->config['priority'] ?? null],
                $extra,
            ));
        }
    }

    private function routeItems(): iterable
    {
        $routes = $this->config['routes'] ?? [];
        if (! is_array($routes) || $this->locale === null) {
            return;
        }

        foreach ($routes as $route => $options) {
            if (is_int($route) && is_string($options)) {
                $route = $options;
                $options = [];
            }
            if (! is_string($route) || $route === '') {
                continue;
            }
            $options = is_array($options) ? $options : [];
            $url = $this->resolveRouteUrl($route, $options['parameters'] ?? []);
            if ($url === null) {
                continue;
            }

            yield new SitemapPage(
                [$this->locale => $url],
                changeFrequency: $this->frequency($options['changefreq'] ?? $this->config['changefreq'] ?? null),
                priority: $this->priority($options['priority'] ?? $this->config['priority'] ?? null),
            );
        }
    }

    private function eloquentItems(array $config): iterable
    {
        $modelClass = $config['model'] ?? null;
        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException('Sitemap eloquent driver requires a valid model class.');
        }

        $query = $modelClass::query();
        foreach ($config['scopes'] ?? [] as $scope) {
            if (! is_string($scope) || $scope === '') {
                continue;
            }
            $query->{$scope}();
        }

        foreach ($config['where_has'] ?? [] as $relation => $constraints) {
            if (! is_string($relation) || ! is_array($constraints)) {
                continue;
            }
            $query->whereHas($relation, function (Builder $builder) use ($constraints) {
                foreach ($constraints as $column => $value) {
                    $builder->where($column, $value);
                }
            });
        }

        $query->orderBy($config['order_by'] ?? 'id');

        $changeFrequency = $this->frequency($config['changefreq'] ?? null);
        $priority = $this->priority($config['priority'] ?? null);
        $urlQueryMap = is_array($config['url_query'] ?? null) ? $config['url_query'] : [];

        foreach ($query->cursor() as $model) {
            $urlModel = $this->resolveUrlModel($model, $config);
            if ($urlModel === null) {
                continue;
            }

            $queryParameters = [];
            foreach ($urlQueryMap as $parameter => $attribute) {
                if (! is_string($parameter) || ! is_string($attribute)) {
                    continue;
                }
                $value = $model->getAttribute($attribute);
                if ($value !== null && $value !== '') {
                    $queryParameters[$parameter] = $value;
                }
            }

            yield new PublicUrlSitemapItem(
                $urlModel,
                $queryParameters,
                $this->locale,
                $changeFrequency,
                $priority,
            );
        }
    }

    private function resolveUrlModel(Model $model, array $config): ?Model
    {
        $urlModel = $config['url_model'] ?? null;
        if ($urlModel === null || $urlModel === '') {
            return $model;
        }
        if (! is_string($urlModel)) {
            return $model;
        }

        if (class_exists($urlModel) && is_subclass_of($urlModel, Model::class)) {
            $foreignKey = $config['url_foreign_key'] ?? 'product_id';
            $related = $urlModel::query()->find($model->getAttribute($foreignKey));

            return $related instanceof Model ? $related : null;
        }

        $related = $model->{$urlModel};
        if ($related instanceof Relation) {
            $related = $related->getResults();
        }

        return $related instanceof Model ? $related : null;
    }

    private function resolveRouteUrl(string $route, array $parameters): ?string
    {
        $resolver = config('crud.sitemap.url_resolver');
        if (is_callable($resolver)) {
            $url = $resolver($route, $parameters, $this->locale);

            return is_string($url) ? $url : null;
        }

        if (is_string($resolver) && class_exists($resolver)) {
            $instance = app($resolver);
            if (method_exists($instance, 'resolve')) {
                $url = $instance->resolve($route, $this->locale, $parameters);

                return is_string($url) ? $url : null;
            }
        }

        if ($this->locale === null) {
            return null;
        }

        $name = $this->locale === config('app.locale') ? $route : $this->locale.'.'.$route;
        if (! \Illuminate\Support\Facades\Route::has($name) && \Illuminate\Support\Facades\Route::has($route)) {
            $name = $route;
        }
        if (! \Illuminate\Support\Facades\Route::has($name)) {
            return null;
        }

        return route($name, $parameters);
    }

    private function frequency(mixed $value): ?SitemapChangeFrequency
    {
        if ($value instanceof SitemapChangeFrequency) {
            return $value;
        }
        if (! is_string($value) || $value === '') {
            return null;
        }

        return SitemapChangeFrequency::tryFrom($value);
    }

    private function priority(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
