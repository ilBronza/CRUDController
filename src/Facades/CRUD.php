<?php

namespace ilBronza\CRUD\Facades;

use Illuminate\Support\Facades\Facade;

class CRUD extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'crud';
    }
}
