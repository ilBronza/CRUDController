<?php

namespace IlBronza\CRUD\Catalog\Filters;

use Illuminate\Database\Eloquent\Builder;

class BooleanFilter extends Filter
{
    protected string $view = 'crud::catalog.filters.boolean';

    public function normalize(mixed $value, Builder $baseQuery): ?bool
    {
        if ($value === null || $value === '')
            return null;

        $this->validate($value, ['value' => ['boolean']]);
        return (bool) $value;
    }

    protected function applyValue(Builder $query, mixed $value): void
    {
        $query->where($this->column($query), $value);
    }

    public function selections(mixed $value): array
    {
        return [['label' => $this->label() . ': ' . __('crud::catalog.' . ($value ? 'yes' : 'no'))]];
    }
}
