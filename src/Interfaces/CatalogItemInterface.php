<?php

namespace IlBronza\CRUD\Interfaces;

use IlBronza\CRUD\Catalog\CatalogItem;

interface CatalogItemInterface
{
    public function toCatalogItem(): CatalogItem;
}
