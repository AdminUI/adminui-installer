<?php

namespace AdminUI\AdminUIInstaller\Commands;

use AdminUI\AdminUIInstaller\Actions\ComposerUpdateAction;
use AdminUI\AdminUIInstaller\Actions\RunMigrationsAction;
use AdminUI\AdminUIInstaller\Actions\SeedDatabaseUpdateAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshSiteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:refresh {--c|no-composer}';

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
        $disableComposerUpdate = $this->option('no-composer');
        $migrationsAction = app(RunMigrationsAction::class);
        $dbUpdateAction = app(SeedDatabaseUpdateAction::class);
        $composerUpdateAction = app(ComposerUpdateAction::class);

        $this->info("Running migrations.");
        $migrationsAction->execute(update: true);
        $this->info("Seeding database updates.");
        $dbUpdateAction->execute();
        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-public',
            '--force' => true,
        ]);

        if (!$disableComposerUpdate) {
            $this->info("Updating composer dependencies.");
            $composerUpdateAction->execute();
        }

        Artisan::call('optimize:clear');

        $this->info('Site has been refreshed');
    }
}
