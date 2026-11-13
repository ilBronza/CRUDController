<?php

namespace IlBronza\CRUD\ResourceFilters;

use Illuminate\Database\Eloquent\Builder;

interface ResourceFilterScope
{
    public function constrain(Builder $query, array $values): void;
}
