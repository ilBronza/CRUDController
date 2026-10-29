<?php

namespace IlBronza\CRUD\Catalog\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class NumberRangeFilter extends Filter
{
    protected string $view = 'crud::catalog.filters.number-range';

    public function normalize(mixed $value, Builder $baseQuery): ?array
    {
        if ($value === null || $value === '')
            return null;

        $rules = ['nullable', 'numeric'];
        foreach (['min', 'max'] as $limit)
            if (isset($this->parameters[$limit]))
                $rules[] = $limit . ':' . $this->parameters[$limit];

        $this->validate($value, ['value' => ['array:min,max'], 'value.min' => $rules, 'value.max' => $rules]);
        $range = array_filter($value, fn ($number) => $number !== null && $number !== '');

        foreach ($range as $number)
            if (! is_finite((float) $number))
                throw ValidationException::withMessages(['filters.' . $this->key() => __('crud::catalog.invalid_range')]);

        if (isset($range['min'], $range['max']) && $range['min'] > $range['max'])
            throw ValidationException::withMessages(['filters.' . $this->key() => __('crud::catalog.invalid_range')]);

        return $range === [] ? null : $range;
    }

    protected function applyValue(Builder $query, mixed $value): void
    {
        if (isset($value['min']))
            $query->where($this->column($query), '>=', $value['min']);
        if (isset($value['max']))
            $query->where($this->column($query), '<=', $value['max']);
    }

    public function selections(mixed $value): array
    {
        $parts = [];
        if (isset($value['min']))
            $parts[] = __('crud::catalog.from', ['value' => $value['min']]);
        if (isset($value['max']))
            $parts[] = __('crud::catalog.to', ['value' => $value['max']]);

        return [['label' => $this->label() . ': ' . implode(' ', $parts)]];
    }
}
