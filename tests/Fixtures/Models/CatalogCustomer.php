<?php

namespace IlBronza\CRUD\Tests\Fixtures\Models;

use IlBronza\CRUD\Catalog\CatalogItem;
use IlBronza\CRUD\Interfaces\CatalogItemInterface;
use Illuminate\Database\Eloquent\Model;

class CatalogCustomer extends Model implements CatalogItemInterface
{
    protected $table = 'catalog_customers';
    protected $guarded = [];
    public $timestamps = false;

    public function toCatalogItem(): CatalogItem
    {
        return new CatalogItem('customer-' . $this->getKey(), $this->company_name, badges: [$this->region]);
    }
}
