<?php

namespace IlBronza\CRUD;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use IlBronza\CRUD\Commands\ControllerCrudParametersTraitCommand;
use IlBronza\CRUD\Commands\CrudBelongsToController;
use IlBronza\CRUD\Commands\CrudController;
use IlBronza\CRUD\Middleware\CRUDAllowedMethods;
use IlBronza\CRUD\Middleware\CRUDCanDelete;
use IlBronza\CRUD\Middleware\CRUDUserAllowedMethod;

class CRUDServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'crud');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'crud');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // $this->app['router']->aliasMiddleware('CRUDAllowedMethods', __DIR__.'/Middleware/CRUDAllowedMethods.php');

        Blade::directive('indexLink', function ($expression) {

            return "<?php

                if(Route::has(($expression) . '.index')) 
                    echo '<a href=\"' . route(($expression) . '.index') . '\">' . __('crud.relations_' . ($expression)) . '</a>';
                elseif(Route::has(Str::plural(($expression)) . '.index')) 
                    echo '<a href=\"' . route(Str::plural(($expression)) . '.index') . '\">' . __('crud.relations_' . ($expression)) . '</a>';
                elseif(is_object(($expression)))
                {
                    if(method_exists(($expression), 'getIndexUrl'))
                        echo '<a href=\"' . ($expression)->getIndexUrl() . '\">' . __('crud.relations_' . Str::plural(lcfirst(class_basename(($expression))))) . '</a>';
                    else
                        echo '<a href=\"' . route(Str::plural(lcfirst(class_basename(($expression)))) . '.index') . '\">' . __('crud.relations_' . Str::plural(lcfirst(class_basename(($expression))))) . '</a>';
                }
                else
                    echo __('crud.relations_' . ($expression));
                ?>";
        });

        $this->commands([
            ControllerCrudParametersTraitCommand::class,
            CrudBelongsToController::class,
            CrudController::class
        ]);

        Response::macro('success', function (string $message = null) {
            $response = [
                'success' => true
            ];

            if($message)
                $response['message'] = $message;

            return Response::make($response);
        });

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('CRUDAllowedMethods', CRUDAllowedMethods::class);
        $router->aliasMiddleware('CRUDCanDelete', CRUDCanDelete::class);

        $this->mergeConfigFrom(__DIR__.'/../config/crud.php', 'crud');

        // Register the service the package provides.
        $this->app->singleton('crud', function ($app) {
            return new CRUD;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['crud'];
    }
    
    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/crud.php' => config_path('crud.php'),
        ], 'crud.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/ilbronza'),
        ], 'crud.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ilbronza'),
        ], 'crud.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/ilbronza'),
        ], 'crud.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
