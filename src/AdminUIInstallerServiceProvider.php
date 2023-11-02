<?php

namespace AdminUI\AdminUIInstaller;

use AdminUI\AdminUI\Facades\AdminUIUpdate;
use AdminUI\AdminUIInstaller\Commands\CheckForUpdateCommand;
use Illuminate\Support\ServiceProvider;
use AdminUI\AdminUIInstaller\Commands\InstallCommand;
use AdminUI\AdminUIInstaller\Commands\UninstallCommand;
use AdminUI\AdminUIInstaller\Services\InstallerService;
use UpdateService;

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

        $this->app->singleton(AdminUIUpdate::class, function () {
            return $this->app->make(UpdateService::class);
        });

        $this->app->singleton(AdminUIInstaller::class, function () {
            return $this->app->make(InstallerService::class);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UninstallCommand::class,
                CheckForUpdateCommand::class
            ]);
        }
    }
}
