<?php

namespace IlBronza\CRUD\Interfaces;

use Illuminate\Database\Eloquent\Model;

/** Optional contract: existing SQL-only filters remain valid in AJAX catalogs. */
interface FrontendCatalogFilterInterface
{
    /** One of text, multiple, number-range or boolean. */
    public function frontendType(): string;

    /** Only this value is exposed to the browser, never the complete model. */
    public function frontendValue(Model $model): mixed;
}
