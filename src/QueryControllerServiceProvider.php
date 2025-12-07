<?php

namespace Masum\QueryController;

use Illuminate\Support\ServiceProvider;

class QueryControllerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/query-controller.php',
            'query-controller'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/config/query-controller.php' => config_path('query-controller.php'),
        ], 'query-controller-config');
    }
}