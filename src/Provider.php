<?php

namespace AdminUI\AdminUIInstaller;

use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{
    public string $root;

    public function register()
    {
        $this->root = dirname(__FILE__, 2);

        config()->set('adminui-installer.root', $this->root);
        $this->mergeConfigFrom($this->root . '/config/adminui-installer.php', 'adminui-installer');

        $this->app->singleton(\AdminUI\AdminUIInstaller\Facades\Install::class, fn() => new \AdminUI\AdminUIInstaller\Services\InstallService);
        $this->app->singleton(\AdminUI\AdminUIInstaller\Facades\Composer::class, fn() => new \AdminUI\AdminUIInstaller\Services\ComposerService);
        $this->app->singleton(\AdminUI\AdminUIInstaller\Facades\Json::class, fn() => new \AdminUI\AdminUIInstaller\Services\JsonService);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \AdminUI\AdminUIInstaller\Commands\InstallCommand::class,
                \AdminUI\AdminUIInstaller\Commands\RefreshSiteCommand::class,
                \AdminUI\AdminUIInstaller\Commands\UninstallCommand::class,
                \AdminUI\AdminUIInstaller\Commands\UpdateCommand::class,
                \AdminUI\AdminUIInstaller\Commands\GoLiveCommand::class,
            ]);
        }

        $spatieBase = base_path('vendor/spatie/laravel-permission');
        if (!config('permission')) {
            $this->publishes([
                $spatieBase . '/config/permission.php' => config_path('permission.php'),
            ], 'spatie-permission-config');

            $this->publishes([
                $spatieBase . '/database/migrations/create_permission_tables.php.stub' => $this->app->databasePath() . '/migrations/2000_01_01_create_permission_tables.php',
            ], 'spatie-permission-migrations');
        }
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'adminui-installer');
        $this->loadRoutesFrom(__DIR__ . '/Routes/install.php');
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
    }
}
