<?php

namespace IlBronza\CRUD;

use IlBronza\CRUD\Providers\ConcurrentUriChecker;
use Illuminate\Support\ServiceProvider;

class ConcurrentUriServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Register the service the package provides.
        $this->app->scoped(ConcurrentUriChecker::class, function ($app) {
            return new ConcurrentUriChecker();
        });

        $this->app->alias(ConcurrentUriChecker::class, 'concurrentUriChecker');
    }
}
