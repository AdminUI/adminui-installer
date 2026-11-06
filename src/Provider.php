<?php

namespace AdminUI\AdminUIInstaller;

use AdminUI\AdminUIInstaller\Commands\GoLiveCommand;
use AdminUI\AdminUIInstaller\Commands\InstallCommand;
use AdminUI\AdminUIInstaller\Commands\RefreshSiteCommand;
use AdminUI\AdminUIInstaller\Commands\UninstallCommand;
use AdminUI\AdminUIInstaller\Commands\UpdateCommand;
use AdminUI\AdminUIInstaller\Facades\Composer;
use AdminUI\AdminUIInstaller\Facades\Install;
use AdminUI\AdminUIInstaller\Facades\Json;
use AdminUI\AdminUIInstaller\Services\ComposerService;
use AdminUI\AdminUIInstaller\Services\InstallService;
use AdminUI\AdminUIInstaller\Services\JsonService;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{
    public string $root;

    public function register()
    {
        $this->root = dirname(__FILE__, 2);

        config()->set('adminui-installer.root', $this->root);
        $this->mergeConfigFrom($this->root.'/config/adminui-installer.php', 'adminui-installer');

        $this->app->singleton(Install::class, fn () => new InstallService);
        $this->app->singleton(Composer::class, fn () => new ComposerService);
        $this->app->singleton(Json::class, fn () => new JsonService);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                RefreshSiteCommand::class,
                UninstallCommand::class,
                UpdateCommand::class,
                GoLiveCommand::class,
            ]);
        }

        $spatieBase = base_path('vendor/spatie/laravel-permission');
        if (! config('permission')) {
            $this->publishes([
                $spatieBase.'/config/permission.php' => config_path('permission.php'),
            ], 'spatie-permission-config');

            $this->publishes([
                $spatieBase.'/database/migrations/create_permission_tables.php.stub' => $this->app->databasePath().'/migrations/2000_01_01_create_permission_tables.php',
            ], 'spatie-permission-migrations');
        }
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/Views', 'adminui-installer');
        $this->loadRoutesFrom(__DIR__.'/Routes/install.php');
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');

        // Optional: the compiled assets are served from the package itself (AssetController), publishing copies them to public/vendor/adminui-installer
        $this->publishes([
            dirname(__DIR__).'/public/build' => public_path('vendor/adminui-installer'),
        ], ['adminui-installer-assets', 'laravel-assets']);
    }
}
