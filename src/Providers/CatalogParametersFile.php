<?php

namespace IlBronza\CRUD\Providers;

use IlBronza\CRUD\Catalog\Filters\FilterFactory;
use IlBronza\CRUD\Interfaces\CatalogDefinitionInterface;

abstract class CatalogParametersFile implements CatalogDefinitionInterface
{
    public function getFiltersParameters(): array
    {
        return [];
    }

    public function filters(): array
    {
        return FilterFactory::makeMany($this->getFiltersParameters());
    }

    public function sorts(): array
    {
        return ['id' => ['label' => __('crud::catalog.default_sort'), 'column' => $this->query()->getModel()->getQualifiedKeyName()]];
    }

    public function defaultSort(): string
    {
        return array_key_first($this->sorts());
    }

    public function perPage(): int
    {
        return 24;
    }

    public function cardView(): string
    {
        return 'crud::catalog.card';
    }

    public function with(): array
    {
        return [];
    }
}
