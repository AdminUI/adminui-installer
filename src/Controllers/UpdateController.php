<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Parsedown;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUIInstaller\Traits\SlimJsonResponse;
use AdminUI\AdminUIInstaller\Actions\RunMigrationsAction;
use AdminUI\AdminUIInstaller\Actions\UnpackReleaseAction;
use AdminUI\AdminUIInstaller\Actions\CleanupInstallAction;
use AdminUI\AdminUIInstaller\Actions\ComposerUpdateAction;
use AdminUI\AdminUIInstaller\Actions\ValidateDownloadAction;
use AdminUI\AdminUIInstaller\Actions\SeedDatabaseUpdateAction;
use AdminUI\AdminUIInstaller\Actions\UpdateVersionEntryAction;
use AdminUI\AdminUIInstaller\Actions\MaintenanceModeEnterAction;
use AdminUI\AdminUIInstaller\Actions\DownloadLatestReleaseAction;
use AdminUI\AdminUIInstaller\Actions\GetLatestReleaseDetailsAction;

class UpdateController extends Controller
{
    use SlimJsonResponse;

    /**
     * Check for an available update for AdminUI
     */
    public function check(GetLatestReleaseDetailsAction $releaseAction)
    {
        $installedVersion = \AdminUI\AdminUI\Models\Configuration::where('name', 'installed_version')->firstOrCreate(
            ['name' => 'installed_version'],
            [
                'label' => 'Installed Version',
                'value' => 'v0.0.1',
                'section' => 'private',
                'type' => 'text',
            ]
        );

        if (file_exists(base_path('packages/adminui')) === false) {
            // return $this->sendFailed('Can\'t update this copy of AdminUI since it appears to be outside the packages folder');
        } elseif (file_exists(base_path('packages/adminui/.git')) === true) {
            return $this->sendFailed('Can\'t update this copy of AdminUI since it is under version control');
        }

        $updateDetails = $releaseAction->execute();

        // Check if update is available
        $updateIsAvailable = version_compare(trim($updateDetails['version'], "v \n\r\t\v\0"), trim($installedVersion->value, "v \n\r\t\v\0"), '>');
        $updateMessage = "There is a new version of AdminUI available!";
        $packageUpdateAvailable = false;

        if (!empty($updateDetails['packages'])) {
            foreach ($updateDetails['packages'] as &$package) {
                $packageVersion = \AdminUI\AdminUI\Models\Configuration::firstOrCreate(
                    ['name' => 'installed_version_' . $package['repo']],
                    [
                        'label' => 'Installed Version of ' . Str::headline($package['name']),
                        'value' => 'v0.0.1',
                        'section' => 'private',
                        'type' => 'text',
                    ]
                );
                $currentVersion = trim($packageVersion->value, "v \n\r\t\v\0");
                $package['updateAvailable'] = version_compare(trim($package['latest']['version'], "v \n\r\t\v\0"), $currentVersion, '>');

                if (!$updateIsAvailable) {
                    $updateMessage = "There are packages with updates available!";
                }
                $packageUpdateAvailable = true;

                $package['currentVersion'] = $currentVersion;
                $Parsedown = new Parsedown();
                $package['latest']['changelog'] = $Parsedown->text($package['latest']['changelog']);
            }
        }

        if ($updateIsAvailable === true || $packageUpdateAvailable) {
            // Calculate if this is a major update for the purpose of warning the user
            $availableMajor = $this->getMajor($updateDetails['version']);
            $installedMajor = $this->getMajor($installedVersion->value);
            $isMajor = $availableMajor > $installedMajor;
            // Parse the .md format changelog into HTML
            $Parsedown = new Parsedown();
            $updateDetails['changelog'] = $Parsedown->text($updateDetails['changelog']);

            return $this->sendSuccess(['update' => $updateDetails, 'message' => $updateMessage, 'isMajor' => $isMajor]);
        } else {
            return $this->sendFailed('You are already using the latest version of AdminUI');
        }
    }

    /**
     * Refresh the AdminUI website
     */
    public function refresh(
        RunMigrationsAction $migrationsAction,
        SeedDatabaseUpdateAction $dbUpdateAction,
        ComposerUpdateAction $composerUpdateAction
    ) {
        $migration = $migrationsAction->execute(update: true);
        $seed = $dbUpdateAction->execute();
        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-public',
            '--force' => true,
        ]);

        $composer = $composerUpdateAction->execute();

        Artisan::call('optimize:clear');

        return $this->sendSuccess('Site refreshed');
    }

    /**
     * Update AdminUI
     */
    public function update(
        Request $request,
        CleanupInstallAction $cleanupAction,
        DownloadLatestReleaseAction $downloadAction,
        ValidateDownloadAction $validateDownloadAction,
        MaintenanceModeEnterAction $downAction,
        UnpackReleaseAction $unpackAction,
        ComposerUpdateAction $composerAction,
        SeedDatabaseUpdateAction $seedAction,
        UpdateVersionEntryAction $versionAction
    ) {
        $log = [];
        $isMaintenance = App::isDownForMaintenance() === true;
        $validated = $request->validate([
            'url' => ['required', 'url'],
            'version' => ['required', 'string'],
            'shasum' => ['required', 'string'],
        ]);

        try {
            $cleanupAction->execute();
            $downloadAction->execute();
            $isValid = $validateDownloadAction->execute(checksum: $validated['shasum']);
        } catch (\Exception $err) {
            return $this->sendFailed($err->getMessage(), $log);
        }

        // User could be in maintenance bypass mode, in which case, leave as is
        if (!$isMaintenance) {
            $bypassKey = $downAction->execute();
            $log[] = 'Maintenance mode enabled';
            $log[] = 'Bypass route is: ' . config('app.url') . '/' . $bypassKey;
        }

        try {
            $unpackAction->execute();
            $composerAction->execute();
            $log[] = 'Updated dependencies';
            $seedAction->execute();
            $log[] = "Ran database seeders";
            Artisan::call('vendor:publish', [
                '--tag' => 'adminui-public',
                '--force' => true,
            ]);
            $log[] = "Published public resources";
            Artisan::call('optimize:clear');
            $log[] = "Cleared application cache";
        } catch (\Exception $err) {
            return $this->sendFailed($err->getMessage(), $log);
        }

        $versionAction->execute(version: $validated['version']);
        $log[] = "Update version number";

        if (!$isMaintenance) {
            Artisan::call('up');
            $log[] = 'Maintenance mode disabled';
        }

        return $this->sendSuccess(log: $log);
    }

    /**
     * getMajor - Extract the MAJOR version number from a semantic versioning string
     */
    private function getMajor(string $version): int
    {
        preg_match('/v?(\d+)\.(\d+)/', $version, $matches);

        return intval($matches[1] ?? 0);
    }
}
