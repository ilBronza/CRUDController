<?php

namespace IlBronza\CRUD\Catalog;

use IlBronza\CRUD\Interfaces\CatalogDefinitionInterface;
use IlBronza\CRUD\Interfaces\CatalogFilterInterface;
use IlBronza\CRUD\Interfaces\CatalogItemInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class Catalog
{
    public array $controls = [];
    public array $values = [];
    public array $activeFilters = [];
    public array $sorts;
    public string $sort;
    public string $cardView;
    public LengthAwarePaginator $results;

    private function __construct(public string $path, public array $context)
    {
        foreach ($context as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $key) ||
                in_array($key, ['filters', 'sort', 'page'], true) || ! is_scalar($value))
                throw new InvalidArgumentException('Catalog context must contain scalar parameters with non-reserved keys.');
        }
    }

    public static function make(CatalogDefinitionInterface $definition, Request $request, array $context = []): self
    {
        $catalog = new self($request->url(), $context);
        try {
            $catalog->build($definition, $request);
        } catch (ValidationException $exception) {
            // GET validation must not redirect to the same invalid URL indefinitely.
            $exception->response = $request->expectsJson()
                ? response()->json(['message' => __('crud::catalog.invalid_filters'), 'errors' => $exception->errors()], 422)
                : response()->view('crud::catalog.invalid', ['errors' => $exception->errors(), 'path' => $catalog->url($catalog->context)], 422);
            throw $exception;
        }

        return $catalog;
    }

    private function build(CatalogDefinitionInterface $definition, Request $request): void
    {
        $base = clone $definition->query();
        if (! $base->getModel() instanceof CatalogItemInterface)
            throw new InvalidArgumentException('Catalog models must implement CatalogItemInterface.');

        $filters = [];
        foreach ($definition->filters() as $filter) {
            if (! $filter instanceof CatalogFilterInterface || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $filter->key()))
                throw new InvalidArgumentException('Invalid catalog filter.');
            if (isset($filters[$filter->key()]))
                throw new InvalidArgumentException('Duplicate catalog filter: ' . $filter->key());

            $filters[$filter->key()] = $filter;
        }

        $this->sorts = $definition->sorts();
        $defaultSort = $definition->defaultSort();
        if (! isset($this->sorts[$defaultSort]) || $definition->perPage() < 1 || $definition->perPage() > 1000)
            throw new InvalidArgumentException('Invalid default catalog sort or page size.');

        foreach ($this->sorts as $sort) {
            if (! isset($sort['label'], $sort['column']) || ! is_string($sort['column']) ||
                ! in_array($sort['direction'] ?? 'asc', ['asc', 'desc'], true))
                throw new InvalidArgumentException('Invalid catalog sort definition.');
        }

        $input = $request->query();
        Validator::make($input, [
            'filters' => ['sometimes', 'array'],
            'sort' => ['sometimes', 'required', 'string', Rule::in(array_keys($this->sorts))],
            'page' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000000'],
        ])->validate();

        $rawFilters = $input['filters'] ?? [];
        if (array_diff(array_keys($rawFilters), array_keys($filters)))
            throw ValidationException::withMessages(['filters' => __('crud::catalog.unknown_filter')]);

        $query = clone $base;
        foreach ($filters as $key => $filter) {
            $value = $filter->normalize($rawFilters[$key] ?? null, clone $base);
            $this->controls[$key] = $filter->control(clone $base);
            if ($value === null)
                continue;

            $this->values[$key] = $value;
            // Each predicate group is ANDed with the original scope. An OR inside
            // a custom filter or local scope cannot escape that outer boundary.
            $query->where(function (Builder $constraints) use ($filter, $value) {
                $filter->apply($constraints, $value);
            });
        }

        $this->sort = $input['sort'] ?? $defaultSort;
        $sort = $this->sorts[$this->sort];
        $column = $base->getModel()->qualifyColumn($sort['column']);
        $keyColumn = $base->getModel()->getQualifiedKeyName();
        $query->reorder()->orderBy($column, $sort['direction'] ?? 'asc');
        if ($column !== $keyColumn)
            $query->orderBy($keyColumn);

        $this->results = $query->with($definition->with())->paginate(
            $definition->perPage(), ['*'], 'page', (int) ($input['page'] ?? 1)
        );
        $this->results->withPath($this->path)->appends($this->parameters());
        // Normalize only the current page, never the entire filtered result set.
        $this->results->setCollection($this->results->getCollection()->map(fn (CatalogItemInterface $model) => $model->toCatalogItem()));
        $this->cardView = $definition->cardView();

        foreach ($this->values as $key => $value) {
            foreach ($filters[$key]->selections($value) as $selection) {
                $selection['url'] = $this->withoutFilter($key, $selection['value'] ?? null);
                $this->activeFilters[] = $selection;
            }
        }
    }

    public function parameters(): array
    {
        return array_merge($this->context, array_filter(['filters' => $this->values, 'sort' => $this->sort], fn ($value) => $value !== []));
    }

    public function url(array $parameters): string
    {
        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        return $this->path . ($query === '' ? '' : '?' . $query);
    }

    public function resetUrl(): string
    {
        return $this->url(array_merge($this->context, ['sort' => $this->sort]));
    }

    public function withoutFilter(string $key, ?string $value = null): string
    {
        $parameters = $this->parameters();
        if ($value === null) {
            unset($parameters['filters'][$key]);
        } else {
            $parameters['filters'][$key] = array_values(array_filter(
                $parameters['filters'][$key], fn ($item) => $item !== $value
            ));
            if ($parameters['filters'][$key] === [])
                unset($parameters['filters'][$key]);
        }

        return $this->url($parameters);
    }
}
