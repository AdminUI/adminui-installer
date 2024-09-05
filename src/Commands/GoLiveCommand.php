<?php

namespace AdminUI\AdminUIInstaller\Commands;

use TruncateDatabaseAction;
use Illuminate\Console\Command;
use function Laravel\Prompts\text;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUI\Models\Configuration;
use App\Actions\CreateDefaultAccountAction;
use AdminUI\AdminUIInstaller\Actions\RunMigrationsAction;
use AdminUI\AdminUIInstaller\Actions\ComposerUpdateAction;
use AdminUI\AdminUIInstaller\Actions\SeedDatabaseUpdateAction;

class RefreshSiteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:golive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to get a site ready for Go Live';

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
        $truncateAction = app(TruncateDatabaseAction::class);
        $defaultAction = app(CreateDefaultAccountAction::class);

        $this->info("Running migrations.");
        $migrationsAction->execute(update: true);

        $this->info("Seeding database updates.");
        $dbUpdateAction->execute();

        $this->info("Truncating tables and setup default account");
        $truncateAction->execute();

        $this->info('Creating default cash sale account and collection address');
        $defaultAction->execute();

        $this->info('Ensuring adminui-public assets are published');
        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-public',
            '--force' => true,
        ]);

        $this->info("Updating composer dependencies.");
        $composerUpdateAction->execute();

        $this->info('Update a few configurations');

        $email = text(
            label: 'Enter Main Email address to receive emails.',
            default: config('settings.email')
        );
        $cc = text(
            label: 'Enter a cc email address to receive copies of emails.',
            default: config('settings.ccemail')
        );
        $support = text(
            label: 'Enter a support email address for the site.',
            default: config('settings.support_email')
        );
        $google = text(
            label: 'Enter the Google Analytics GTM ID for the site.',
            default: config('settings.google_analytics')
        );
        $recaptcha = text(
            label: 'Enter the Google Recaptcha Site Key for the site.',
            default: config('settings.recaptcha_site_key')
        );
        $recaptchaSecret = text(
            label: 'Enter the Google Recaptcha Secret Key for the site.',
            default: config('settings.recaptcha_secret_key')
        );

        Configuration::where('name', 'email')->update(['value' => $email]);
        Configuration::where('name', 'ccemail')->update(['value' => $cc]);
        Configuration::where('name', 'support_email')->update(['value' => $support]);
        Configuration::where('name', 'analytics__gtm_id')->update(['value' => $google]);
        Configuration::where('name', 'recaptcha_site_key')->update(['value' => $recaptcha]);
        Configuration::where('name', 'recaptcha_secret_key')->update(['value' => $recaptchaSecret]);

        $this->info('Hold on, we\'re nearly there, just one more step...');
        $this->info('Clearing cache');
        Artisan::call('optimize:clear');
        Artisan::call('optimize');

        $this->info('Site has been prepared for Go Live.');
        $this->info('Ensure the payment gateways are live, webhooks are updated and any other settings are correct.');
        $this->info('Please check the site and ensure everything is working as expected.');
    }
}
