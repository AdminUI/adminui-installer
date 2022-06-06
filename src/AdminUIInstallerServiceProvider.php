<?php

namespace AdminUI\AdminUIInstaller;

use Illuminate\Support\ServiceProvider;

class AdminUIInstallerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'adminui-installer');
        $this->loadViewsFrom(__DIR__ . '/views', 'adminui-installer');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('adminui-installer.php'),
            ], 'adminui-installer-config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/adminui-installer'),
            ], 'views');*/

            // Publishing assets.
            $this->publishes([
                __DIR__ . '/assets' => public_path('adminui-installer'),
            ], 'adminui-installer-assets');

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/adminui-installer'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'adminui-installer');

        // Register the main class to use with the facade
        $this->app->singleton('adminui-installer', function () {
            return new AdminuiInstaller;
        });
    }
}
