<?php

namespace IlBronza\CRUD\Catalog\Filters;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class TextFilter extends Filter
{
    protected string $view = 'crud::catalog.filters.text';

    public function normalize(mixed $value, Builder $baseQuery): ?string
    {
        $this->validate($value, ['value' => ['nullable', 'string', 'max:' . ($this->parameters['maxLength'] ?? 200)]]);
        $value = trim($value ?? '');
        return $value === '' ? null : $value;
    }

    protected function applyValue(Builder $query, mixed $value): void
    {
        $columns = $this->parameters['columns'] ?? [$this->column($query)];
        if (! is_array($columns) || $columns === [])
            throw new InvalidArgumentException("Missing search columns for {$this->name}.");

        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value) . '%';
        $grammar = $query->getQuery()->getGrammar();

        $query->where(function (Builder $search) use ($columns, $pattern, $grammar) {
            foreach ($columns as $column) {
                $column = $grammar->wrap($search->getModel()->qualifyColumn($column));
                $search->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$pattern]);
            }
        });
    }
}
