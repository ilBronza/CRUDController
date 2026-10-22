<?php

namespace IlBronza\CRUD\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface CatalogDefinitionInterface
{
    public function query(): Builder;

    /** @return CatalogFilterInterface[] */
    public function filters(): array;

    /** @return array<string, array{label: string, column: string, direction?: string}> */
    public function sorts(): array;

    public function defaultSort(): string;
    public function perPage(): int;
    public function cardView(): string;
    public function with(): array;
}
