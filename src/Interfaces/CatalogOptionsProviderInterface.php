<?php

namespace IlBronza\CRUD\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface CatalogOptionsProviderInterface
{
    /** Derive value => label options from this scoped, unfiltered catalog query. */
    public function options(Builder $baseQuery): array;
}
