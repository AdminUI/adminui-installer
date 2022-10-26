<?php

namespace AdminUI\AdminUIInstaller\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUIInstaller\Services\DatabaseService;
use AdminUI\AdminUIInstaller\Services\InstallerService;
use AdminUI\AdminUIInstaller\Services\ApplicationService;
use Facades\AdminUI\AdminUIInstaller\Controllers\UninstallController;

class InstallCommand extends Command
{
    protected $signature = 'adminui:install';

    protected $description = 'Install AdminUI into an existing application';

    /**
     * $zipPath - Path to use for the .zip installer relative to default Storage
     *
     * @var string
     */
    protected $zipPath = 'adminui-installer.zip';
    /**
     * $extractPath - Path to use for the extracted installer relative to default Storage
     *
     * @var string
     */
    protected $extractPath = 'adminui-installer';

    public function handle()
    {
        $service = new InstallerService;
        $dbService = new DatabaseService;
        $appService = new ApplicationService;

        $key = env('ADMINUI_LICENCE_KE');

        if (empty($key)) {
            print_r("No licence key found. Please enter it as ADMINUI_LICENCE_KEY in your .env file");
            die();
        }

        $isInstalled = $service->checkIfInstalled();

        if (true === $isInstalled) {
            print_r('AdminUI is already installed. Please use the update function from your installation instead');
            die();
        }

        $hasDbConnection = $dbService->check();

        if (false === $hasDbConnection) {
            print_r("No database connection available. Check your DB settings and try again");
            die();
        }

        $installDetails = $service->checkLatestRelease($key);
        $summary = $service->downloadPackage($key, $installDetails['url'], $this->zipPath);

        print_r($summary);

        $packageIsValid = $service->validatePackage($installDetails['shasum'], $this->zipPath);


        if ($packageIsValid === false) {
            print_r("Invalid installer package");
            die();
        }

        $service->extract($this->zipPath, $this->extractPath);
        $appService->composerUpdate();
        $appService->flushCache();

        Artisan::call('vendor:publish', [
            '--tag'      => 'adminui-public',
            '--force'    => true
        ]);

        $service->finish($installDetails['version']);

        print_r("Installation complete!");
    }
}
