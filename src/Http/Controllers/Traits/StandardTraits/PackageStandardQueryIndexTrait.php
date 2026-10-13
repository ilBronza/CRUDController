<?php

namespace IlBronza\CRUD\Http\Controllers\Traits\StandardTraits;

/**
 * Opts an index into the query-provider contract without changing legacy controllers.
 */
trait PackageStandardQueryIndexTrait
{
    use PackageStandardIndexTrait;

    public function getIndexQuery()
    {
        return $this->getStandardIndexQuery();
    }

    public function getIndexElements()
    {
        return $this->getIndexQuery()->get();
    }
}
