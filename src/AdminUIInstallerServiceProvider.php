<?php

namespace AdminUI\AdminUIInstaller;

use AdminUI\AdminUIInstaller\Commands\UninstallCommand;
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
        $this->loadViewsFrom(__DIR__ . '/Views', 'adminui-installer');
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        config()->set('adminui-installer.base_path', dirname(__DIR__));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('adminui-installer.php'),
            ], 'adminui-installer-config');
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                UninstallCommand::class,
            ]);
        }
    }
}
