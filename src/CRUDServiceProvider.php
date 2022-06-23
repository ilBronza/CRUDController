<?php

namespace IlBronza\CRUD;

use IlBronza\CRUD\Commands\ControllerCrudParametersTraitCommand;
use IlBronza\CRUD\Commands\CrudBelongsToController;
use IlBronza\CRUD\Commands\CrudController;
use IlBronza\CRUD\Middleware\CRUDAllowedMethods;
use IlBronza\CRUD\Middleware\CRUDCanDelete;
use IlBronza\CRUD\Middleware\CRUDParseAjaxBooleansAndNull;
use IlBronza\CRUD\Middleware\CRUDParseComasAndDots;
use IlBronza\CRUD\Middleware\CRUDUserAllowedMethod;
use IlBronza\CRUD\ResourceRegistrar;
use Illuminate\Routing\ResourceRegistrar as BaseResourceRegistrar;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;


class CRUDServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('CRUDParseAjaxBooleansAndNull', CRUDParseAjaxBooleansAndNull::class);
        $router->aliasMiddleware('CRUDParseComasAndDots', CRUDParseComasAndDots::class);

        if(config('crud.useConcurrentRequestsAlert'))
            $router->aliasMiddleware('CRUDParseComasAndDots', CRUDConcurrentUrlAlert::class);

        app()->bind(BaseResourceRegistrar::class, function () {
            return new ResourceRegistrar(app()->make(Router::class));
        });

        /**
         * child Resource for dependants models, 
         * Route::childResource(array $models, string $controller, array $parameters);
         *
         * @param array $models => the model sequences to build the route and the names
         * @param string $controller => the target controller to handle the requested urls,
         * @param array $parameters => the parameters to be added to route methods can be: 
         *              'childKey' as the model's key field to perform Dependency Injection and ownerships controls,
         *              'middleware' as string or array to describes the middlewares to be called.
         **/
        Router::macro('childResource', function(array $models, string $controller, array $parameters = [])
        {
            $nameString = implode(".", $models);

            $prefixPieces = [];

            do
            {
                $model = array_shift($models);

                $prefixPieces[] = Str::snake($model, '-');
                $prefixPieces[] = '{' . Str::singular($model, '-') . '}';

            } while(count($models) > 1);

            $prefixString = implode("/", $prefixPieces);


            $model = array_shift($models);

            $childUrl = Str::snake($model, '-');
            $singularChild = Str::singular($model);


            $childKey = $parameters['childKey'] ?? 'id';
            $middleware = $parameters['middleware'] ?? null;

            Route::prefix($prefixString)
                ->group(function () use($childUrl, $singularChild, $controller, $childKey, $nameString, $middleware)
            {
                Route::get($childUrl,                                                       $controller . '@index')     ->name($nameString . '.index')->middleware($middleware);
                Route::get($childUrl . '/deleted',                                          $controller . '@deleted')   ->name($nameString . '.deleted')->middleware($middleware);
                Route::get($childUrl . '/archived',                                         $controller . '@archived')  ->name($nameString . '.archived')->middleware($middleware);

                Route::get($childUrl . '/create',                                           $controller . '@create')    ->name($nameString . '.create')->middleware($middleware);
                Route::post($childUrl,                                                      $controller . '@store')     ->name($nameString . '.store')->middleware($middleware);

                Route::get($childUrl . '/{' . $singularChild . ':' . $childKey . '}',       $controller . '@show')      ->name($nameString . '.show')->middleware($middleware);
                Route::get($childUrl . '/{' . $singularChild . ':' . $childKey . '}/edit',  $controller . '@edit')      ->name($nameString . '.edit')->middleware($middleware);
                Route::put($childUrl . '/{' . $singularChild . ':' . $childKey . '}',       $controller . '@update')    ->name($nameString . '.update')->middleware($middleware);

                Route::delete($childUrl . '/{' . $singularChild . ':' . $childKey . '}',    $controller . '@destroy')   ->name($nameString . '.destroy')->middleware($middleware);
            });
        });


        $this->loadViewsFrom(__DIR__.'/../resources/views', 'crud');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'crud');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // $this->app['router']->aliasMiddleware('CRUDAllowedMethods', __DIR__.'/Middleware/CRUDAllowedMethods.php');

        Blade::directive('indexLink', function ($expression) {

            return "<?php

                if(Route::has(($expression) . '.index')) 
                    echo '<a href=\"' . route(($expression) . '.index') . '\">' . __('crudModels.' . ($expression)) . '</a>';
                elseif(Route::has(Str::plural(($expression)) . '.index')) 
                    echo '<a href=\"' . route(Str::plural(($expression)) . '.index') . '\">' . __('crudModels.' . ($expression)) . '</a>';
                elseif(is_object(($expression)))
                {
                    if(method_exists(($expression), 'getIndexUrl'))
                        echo '<a href=\"' . ($expression)->getIndexUrl() . '\">' . __('crudModels.' . Str::plural(lcfirst(class_basename(($expression))))) . '</a>';
                    else
                        echo '<a href=\"' . route(Str::plural(lcfirst(class_basename(($expression)))) . '.index') . '\">' . __('crudModels.' . Str::plural(lcfirst(class_basename(($expression))))) . '</a>';
                }
                else
                    echo __('crudModels.' . ($expression));
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
        $router->aliasMiddleware('CRUDParseAjaxBooleansAndNull', CRUDParseAjaxBooleansAndNull::class);
        $router->aliasMiddleware('CRUDParseComasAndDots', CRUDParseComasAndDots::class);

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
        $this->publishes([
            __DIR__.'/../resources/assets' => base_path('resources'),
        ], 'crud.assets');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'crud-migrations');


        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/ilbronza'),
        ], 'crud.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
