<?php

namespace IlBronza\CRUD\Catalog\Filters;

use IlBronza\CRUD\Interfaces\CatalogOptionsProviderInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class MultipleFilter extends Filter
{
    protected string $view = 'crud::catalog.filters.multiple';
    protected ?array $resolvedOptions = null;

    protected function options(Builder $baseQuery): array
    {
        if ($this->resolvedOptions !== null)
            return $this->resolvedOptions;

        $options = $this->parameters['options'] ?? [];
        if (is_string($options) && is_subclass_of($options, CatalogOptionsProviderInterface::class))
            $options = app($options);

        if ($options instanceof CatalogOptionsProviderInterface)
            $options = $options->options(clone $baseQuery);

        if (! is_array($options))
            throw new InvalidArgumentException("Invalid options provider for {$this->name}.");

        foreach ($options as $label)
            if (! is_string($label))
                throw new InvalidArgumentException('Catalog option labels must be strings.');

        return $this->resolvedOptions = $options;
    }

    public function control(Builder $baseQuery): array
    {
        return array_merge(parent::control($baseQuery), ['options' => $this->options($baseQuery)]);
    }

    public function normalize(mixed $value, Builder $baseQuery): ?array
    {
        if ($value === null || $value === '')
            return null;

        $this->validate($value, [
            'value' => ['array', 'list', 'max:' . ($this->parameters['maxSelections'] ?? 100)],
            'value.*' => ['required', 'string', Rule::in(array_map('strval', array_keys($this->options($baseQuery))))],
        ]);

        $values = array_values(array_unique($value));
        return $values === [] ? null : $values;
    }

    protected function applyValue(Builder $query, mixed $value): void
    {
        $query->whereIn($this->column($query), $value);
    }

    public function selections(mixed $value): array
    {
        return array_map(fn ($item) => [
            'label' => $this->label() . ': ' . ($this->resolvedOptions[$item] ?? $item),
            'value' => $item,
        ], $value);
    }
}
