<?php

namespace IlBronza\CRUD\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface CatalogFilterInterface
{
    public function key(): string;
    public function label(): string;

    /** Return the control view and its configuration. No HTML belongs here. */
    public function control(Builder $baseQuery): array;

    /** Validate before applying. null means inactive; false and zero are active. */
    public function normalize(mixed $value, Builder $baseQuery): mixed;

    /** Add predicates to the supplied query; do not replace or execute it. */
    public function apply(Builder $query, mixed $value): void;

    /** @return array<array{label: string, value?: string}> */
    public function selections(mixed $value): array;
}
