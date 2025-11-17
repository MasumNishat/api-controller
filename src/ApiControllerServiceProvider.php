<?php

namespace Masum\ApiController;

use Illuminate\Support\ServiceProvider;

class ApiControllerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/api-controller.php',
            'api-controller'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/config/api-controller.php' => config_path('api-controller.php'),
        ], 'api-controller-config');
    }
}