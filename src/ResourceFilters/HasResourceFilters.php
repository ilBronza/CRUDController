<?php

namespace IlBronza\CRUD\ResourceFilters;

trait HasResourceFilters
{
    public static function bootHasResourceFilters(): void
    {
        static::addGlobalScope(new SessionResourceScope());
    }
}
