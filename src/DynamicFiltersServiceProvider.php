<?php

namespace MohamedSaad\DynamicFilters;

use Illuminate\Support\ServiceProvider;

class DynamicFiltersServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register()
    {
        // Merge package config with the application's config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dynamic-filters.php',
            'dynamic-filters'
        );
    }

    /**
     * Bootstrap package services.
     */
    public function boot()
    {
        // Publish the config file so developers can customize it
        $this->publishes([
            __DIR__ . '/../config/dynamic-filters.php' => config_path('dynamic-filters.php'),
        ], 'dynamic-filters-config');
    }
}
