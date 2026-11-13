<?php

namespace IlBronza\CRUD\ResourceFilters;

use Closure;
use IlBronza\CRUD\CRUD;
use IlBronza\Buttons\Button;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class ResourceFilters
{
    public const SESSION_KEY = 'crud.resource_filters';

    private array $resources = [];
    private array $state = [];
    private ?Request $request = null;
    private bool $read = false;
    private ?string $context = null;
    private array $backgroundTasks = [];

    /** Called inside StartSession, before route model binding. Never from model boot. */
    public function duringRequest(Request $request, Closure $next): mixed
    {
        if (! $request->hasSession() || ! $request->session()->isStarted())
            return $next($request);

        $configured = array_filter(config('resource-filters.resources', []),
            fn (array $resource) => $resource['enabled'] ?? false);
        $stored = $request->session()->get(self::SESSION_KEY, []);
        if (! $configured) {
            if ($stored)
                $request->session()->forget(self::SESSION_KEY);
            return $next($request);
        }
        if (! $request->user())
            return $next($request);

        try {
            foreach ($configured as $key => $config) {
                if (isset($config['ability']) && Gate::denies($config['ability']))
                    continue;
                $resource = new FilterResource($key, $config);
                $this->resources[$key] = $resource;
                $values = $resource->values((array) ($stored[$key] ?? []), false);
                if ($values !== [])
                    $this->state[$key] = $values;
            }

            $this->request = $request;
            $this->read = $this->isConsultation($request)
                && ! $request->routeIs(...config('resource-filters.except_routes', []));
            $this->sync($stored);

            return $next($request);
        } finally {
            $this->request = null;
            $this->read = false;
            $this->resources = $this->state = $this->backgroundTasks = [];
            $this->context = null;
        }
    }

    private function isConsultation(Request $request): bool
    {
        if ($request->isMethodSafe())
            return true;
        if (! $request->isMethod('POST'))
            return false;

        $route = $request->route();
        return $request->routeIs('crud.ajaxSearch', ...config('resource-filters.read_routes', []))
            || ($route?->getActionMethod() === 'index' && is_a($route->getControllerClass(), CRUD::class, true));
    }

    private function sync(mixed $previous): void
    {
        ksort($this->state);
        if ($previous !== $this->state) {
            if ($this->state === [])
                $this->request->session()->forget(self::SESSION_KEY);
            else
                $this->request->session()->put(self::SESSION_KEY, $this->state);
        }
        $this->context = hash('sha256', serialize([
            $this->request->session()->getId(),
            $this->request->user()->getAuthIdentifier(),
            app()->getLocale(),
            config('resource-filters.resources'),
            array_keys($this->resources),
            config('resource-filters.navbar'),
            $this->read,
            $this->state,
        ]));
    }

    public function resource(string $key): FilterResource
    {
        abort_unless(isset($this->resources[$key]), 404);
        return $this->resources[$key];
    }

    public function state(): array
    {
        return $this->backgroundTasks ? [] : $this->state;
    }

    public function active(): array
    {
        return $this->read ? $this->state() : [];
    }

    public function save(string $key, array $input): void
    {
        $values = $this->resource($key)->values($input, true);
        $previous = $this->state;
        if ($values === [])
            unset($this->state[$key]);
        else
            $this->state[$key] = $values;
        $this->sync($previous);
    }

    public function resetAll(): void
    {
        abort_unless($this->resources, 404);
        $previous = $this->state;
        $this->state = [];
        $this->sync($previous);
    }

    public static function currentCacheContext(): ?string
    {
        return app()->bound(self::class) ? app(self::class)->cacheContext() : null;
    }

    public function cacheContext(): ?string
    {
        return $this->backgroundTasks ? null : $this->context;
    }

    /** A sync queue job can run inside a live HTTP request, including nested jobs. */
    public function enteringBackgroundTask(object $task): void
    {
        if ($this->request)
            $this->backgroundTasks[spl_object_id($task)] = true;
    }

    public function leavingBackgroundTask(object $task): void
    {
        unset($this->backgroundTasks[spl_object_id($task)]);
    }

    public function apply(Builder $builder): void
    {
        foreach ($this->active() as $key => $values) {
            $resource = $this->resources[$key];
            if (! $resource->matches($builder->getModel()))
                continue;
            $scope = app($resource->config['scope']);
            $builder->where(fn (Builder $group) => $scope->constrain($group, $values));
        }
    }

    public function button(FilterResource $resource): Button
    {
        $count = count($this->state[$resource->key] ?? []);
        return Button::create([
            'name' => 'crud-resource-filter-' . $resource->key,
            'translatedText' => e($resource->label()),
            'count' => $count,
            'icon' => 'filter',
            'lightbox' => true,
            'href' => route('crud.resourceFilters.edit', [
                'resource' => $resource->key,
                'iframed' => 1,
            ]),
        ]);
    }

    public function menuButtons(): array
    {
        return array_map(fn (FilterResource $resource) => $this->button($resource), $this->resources);
    }

    public function header(): ?View
    {
        $view = config('resource-filters.header_view');
        if (! $view || ! $this->resources || (! $this->state && config('resource-filters.navbar')))
            return null;

        $summaries = [];
        foreach ($this->state as $key => $values) {
            $resource = $this->resources[$key];
            foreach ($values as $name => $value) {
                $definition = $resource->definitions[$name];
                $items = (array) $value;
                $labels = isset($definition['options']) ? $definition['options']($items) : null;
                $descriptions = [];
                foreach ($items as $item) {
                    if ($labels !== null)
                        $descriptions[] = $labels[is_bool($item) ? (int) $item : $item] ?? __('crud::resource-filters.unavailable');
                    elseif (is_bool($item))
                        $descriptions[] = __('crud::resource-filters.' . ($item ? 'yes' : 'no'));
                    else
                        $descriptions[] = $item;
                }
                $summaries[$key][] = [
                    'label' => __($definition['label']),
                    'selection' => implode(', ', $descriptions),
                ];
            }
        }
        return view($view, [
            'summaries' => $summaries,
            'buttons' => $this->menuButtons(),
            'showResourceButtons' => ! config('resource-filters.navbar'),
            'count' => array_sum(array_map('count', $this->state)),
        ]);
    }
}
