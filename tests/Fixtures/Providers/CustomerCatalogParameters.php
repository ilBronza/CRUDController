<?php

namespace IlBronza\CRUD\Tests\Fixtures\Providers;

use IlBronza\CRUD\Providers\CatalogParametersFile;
use IlBronza\CRUD\Tests\Fixtures\Models\CatalogCustomer;
use Illuminate\Database\Eloquent\Builder;

class CustomerCatalogParameters extends CatalogParametersFile
{
    public function query(): Builder
    {
        return CatalogCustomer::query()->where('tenant_id', 1);
    }

    public function getFiltersParameters(): array
    {
        return ['company' => ['type' => 'text', 'label' => 'Ragione sociale', 'columns' => ['company_name']]];
    }

    public function cardView(): string
    {
        return 'catalog-fixtures::customer-card';
    }
}
