<?php

namespace IlBronza\CRUD\ResourceFilters;

use Illuminate\Database\Eloquent\Builder;

class IdentifierFilterScope implements ResourceFilterScope
{
    public function constrain(Builder $query, array $values): void
    {
        if (isset($values['ids']))
            $query->whereKey($values['ids']);
    }
}
