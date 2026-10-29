<?php

namespace IlBronza\CRUD\Tests\Fixtures\Providers;

use IlBronza\CRUD\Providers\CatalogParametersFile;
use IlBronza\CRUD\Tests\Fixtures\Models\CatalogDocument;
use Illuminate\Database\Eloquent\Builder;

class DocumentCatalogParameters extends CatalogParametersFile
{
    public function query(): Builder
    {
        return CatalogDocument::query()->where('tenant_id', 1);
    }

    public function getFiltersParameters(): array
    {
        return [
            'search' => ['type' => 'text', 'label' => 'Cerca', 'columns' => ['subject', 'summary']],
            'status' => ['type' => 'multiple', 'column' => 'status', 'label' => 'Stato', 'options' => ['draft' => 'Bozza', 'sent' => 'Inviato']],
            'amount' => ['type' => 'number-range', 'column' => 'amount', 'label' => 'Importo', 'min' => 0],
            'approved' => ['type' => 'boolean', 'column' => 'approved', 'label' => 'Approvato'],
            'regions' => ['type' => 'multiple', 'scope' => 'inRegions', 'label' => 'Regione', 'options' => ['north' => 'Nord', 'south' => 'Sud']],
        ];
    }

    public function sorts(): array
    {
        return [
            'subject' => ['label' => 'Titolo', 'column' => 'subject'],
            'amount_desc' => ['label' => 'Importo decrescente', 'column' => 'amount', 'direction' => 'desc'],
        ];
    }

    public function perPage(): int
    {
        return 2;
    }
}
