<?php

namespace IlBronza\CRUD\ResourceFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class SessionResourceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound(ResourceFilters::class))
            app(ResourceFilters::class)->apply($builder);
    }
}
