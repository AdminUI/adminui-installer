<?php

namespace AdminUI\AdminUIInstaller\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUI\Models\Configuration;
use AdminUI\AdminUIInstaller\Actions\UnpackReleaseAction;
use AdminUI\AdminUIInstaller\Actions\CleanupInstallAction;
use AdminUI\AdminUIInstaller\Actions\ComposerUpdateAction;
use AdminUI\AdminUIInstaller\Actions\ValidateDownloadAction;
use AdminUI\AdminUIInstaller\Actions\SeedDatabaseUpdateAction;
use AdminUI\AdminUIInstaller\Actions\UpdateVersionEntryAction;
use AdminUI\AdminUIInstaller\Actions\MaintenanceModeEnterAction;
use AdminUI\AdminUIInstaller\Actions\DownloadLatestReleaseAction;
use AdminUI\AdminUIInstaller\Actions\GetLatestReleaseDetailsAction;

class UpdateCommand extends Command
{
    protected $signature = 'adminui:update';

    protected $description = 'Update the AdminUI version in a Laravel application';

    public function handle()
    {
        $installedVersion = Configuration::firstWhere('name', 'installed_version');
        $releaseAction = app(GetLatestReleaseDetailsAction::class);
        $updateDetails = $releaseAction->execute();

        $updateIsAvailable = version_compare(trim($updateDetails['version'], "v \n\r\t\v\0"), trim($installedVersion->value, "v \n\r\t\v\0"), '>');

        if ($updateIsAvailable === false) {
            return $this->info("You're already on the latest version");
        } else {
            $shouldInstall = $this->confirm("Version " . $updateDetails['version'] . " is available, do you wish to update?");
            if (!$shouldInstall) {
                return $this->info('Exited without updating.');
            }
        }

        $downAction = app(MaintenanceModeEnterAction::class);
        $cleanupAction = app(CleanupInstallAction::class);
        $downloadAction = app(DownloadLatestReleaseAction::class);
        $validateDownloadAction = app(ValidateDownloadAction::class);
        $unpackAction = app(UnpackReleaseAction::class);
        $composerAction = app(ComposerUpdateAction::class);
        $seedAction = app(SeedDatabaseUpdateAction::class);
        $versionAction = app(UpdateVersionEntryAction::class);

        $isMaintenance = App::isDownForMaintenance() === true;

        $cleanupAction->execute();
        $downloadAction->execute();
        $isValid = $validateDownloadAction->execute(checksum: $updateDetails['shasum']);

        if (!$isValid) {
            return $this->error("Down checksum does not match. Aborting update.");
        }

        if (!$isMaintenance) {
            $bypassKey = $downAction->execute();
            $this->info('Maintenance mode enabled');
            $this->info('Bypass route is: ' . config('app.url') . '/' . $bypassKey);
        }

        $unpackAction->execute();
        $composerAction->execute();
        $seedAction->execute();
        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-public',
            '--force' => true,
        ]);
        Artisan::call('optimize:clear');

        $versionAction->execute(version: $updateDetails['version']);

        if (!$isMaintenance) {
            Artisan::call('up');
        }

        $this->info("Update complete");
    }
}
