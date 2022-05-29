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
        $this->app->singleton(ConcurrentUriChecker::class, function ($app) {
            return new ConcurrentUriChecker();
        });

        $this->app->singleton('concurrentUriChecker', function ($app) {
            return app()->get(ConcurrentUriChecker::class);
        });
    }
}
