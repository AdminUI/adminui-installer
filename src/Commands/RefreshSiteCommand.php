<?php

namespace AdminUI\AdminUIInstaller\Commands;

use AdminUI\AdminUIInstaller\Actions\ComposerUpdateAction;
use AdminUI\AdminUIInstaller\Actions\RunMigrationsAction;
use AdminUI\AdminUIInstaller\Actions\SeedDatabaseUpdateAction;
use AdminUI\AdminUIInstaller\Services\ApplicationService;
use AdminUI\AdminUIInstaller\Services\DatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshSiteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh database values and clear cache';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $migrationsAction = app(RunMigrationsAction::class);
        $dbUpdateAction = app(SeedDatabaseUpdateAction::class);
        $composerUpdateAction = app(ComposerUpdateAction::class);

        $migrationsAction->execute(update: true);
        $dbUpdateAction->execute();
        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-public',
            '--force' => true,
        ]);

        $composerUpdateAction->execute();

        Artisan::call('optimize:clear');

        $this->info('Site has been refreshed');
    }
}
