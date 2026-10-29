<?php

namespace IlBronza\CRUD\Tests\Fixtures\Models;

use IlBronza\CRUD\Catalog\CatalogItem;
use IlBronza\CRUD\Interfaces\CatalogItemInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogDocument extends Model implements CatalogItemInterface
{
    protected $table = 'catalog_documents';
    protected $guarded = [];
    public $timestamps = false;

    public function toCatalogItem(): CatalogItem
    {
        return new CatalogItem($this->getKey(), $this->subject ?? '', $this->summary);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CatalogCustomer::class, 'customer_id');
    }

    public function scopeInRegions(Builder $query, array $regions): void
    {
        $query->whereHas('customer', fn (Builder $customer) => $customer->whereIn('region', $regions));
    }

    public function scopeAnyStatus(Builder $query, array $statuses): void
    {
        foreach ($statuses as $status)
            $query->orWhere('status', $status);
    }
}
