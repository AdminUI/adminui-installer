<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Parsedown;
use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Services\DatabaseService;
use AdminUI\AdminUIInstaller\Services\InstallerService;
use AdminUI\AdminUIInstaller\Services\ApplicationService;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

class UpdateController extends BaseInstallController
{
    public function __construct(
        protected ApplicationService $appService,
        protected DatabaseService $dbService,
        protected InstallerService $installerService
    ) {
    }

    /**
     * checkUpdate - Route controller for checking update availability
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function checkUpdate(Request $request)
    {
        $installedVersion = \AdminUI\AdminUI\Models\Configuration::where('name', 'installed_version')->firstOrCreate(
            ['name'  => 'installed_version'],
            [
                'label' => 'Installed Version',
                'value' => 'v0.0.1',
                'section' => 'private',
                'type'  => 'text'
            ]
        );

        if (file_exists(base_path('packages/adminui')) === false) {
            return $this->sendFailed('Can\'t update this copy of AdminUI since it appears to be outside the packages folder');
        } else if (file_exists(base_path('packages/adminui/.git')) === true) {
            return $this->sendFailed('Can\'t update this copy of AdminUI since it is under version control');
        }

        // Fetch the available version from the MGMT server
        $updateDetails = $this->installerService->checkLatestRelease(config('adminui.licence_key'));

        // Check if update is available
        $updateIsAvailable = version_compare($updateDetails['version'], $installedVersion->value, '>');


        if (true === $updateIsAvailable) {
            // Calculate if this is a major update for the purpose of warning the user
            $availableMajor = $this->getMajor($updateDetails['version']);
            $installedMajor = $this->getMajor($installedVersion->value);
            $isMajor = $availableMajor > $installedMajor;
            // Parse the .md format changelog into HTML
            $Parsedown = new Parsedown();
            $updateDetails['changelog'] = $Parsedown->text($updateDetails['changelog']);

            return $this->sendSuccess(['update' => $updateDetails, 'message' => 'There is a new version of AdminUI available!', 'isMajor' => $isMajor]);
        } else {
            return $this->sendFailed("You are already using the latest version of AdminUI");
        }
    }

    /**
     * getMajor - Extract the MAJOR version number from a semantic versioning string
     *
     * @param  string $version
     * @return int
     */
    private function getMajor(string $version): int
    {
        return preg_match('/v?(\d+)\.(\d+)/', $version);
    }

    /**
     * updateSystem - Route controller for installing an update
     *
     * @param  mixed $request
     * @return void
     */
    public function updateSystem(Request $request)
    {
        $isMaintenance = App::isDownForMaintenance() === true;
        $validated = $request->validate([
            'url'   => ['required', 'url'],
            'version' => ['required', 'string'],
            'shasum'    => ['required', 'string']
        ]);

        $this->appService->cleanUpdateDirectory($this->zipPath, $this->extractPath);

        try {
            $this->installerService->downloadPackage(config('adminui.licence_key'), $validated['url'], $this->zipPath);
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }
        try {
            $this->installerService->validatePackage($validated['shasum'], $this->zipPath);
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }

        // User could be in maintenance bypass mode, in which case, leave as is
        if (!$isMaintenance) {
            $this->appService->down();
            $this->addOutput("Entering maintenance mode:", true);
        }


        $zipPath = Storage::path($this->zipPath);

        $archive = new ZipArchive;
        if ($archive->open($zipPath) === true) {
            $this->addOutput("Extract complete");

            $result = $this->installerService->installArchive($archive, $this->extractPath);
            $this->appService->checkForComposerUpdate($result['destination']);

            $dbOutput = $this->dbService->migrateAndSeedUpdate();
            foreach ($dbOutput as $line) {
                $this->addOutput($line);
            }

            Artisan::call('vendor:publish', [
                '--provider' => 'AdminUI\AdminUI\Provider',
                '--tag'      => 'adminui-public',
                '--force'    => true
            ]);
            $this->addOutput("Output:", true);
            $this->appService->flushCache();

            // Update the installed version in the database configurations table
            $this->installerService->updateVersionEntry($validated['version']);

            if (!$isMaintenance) {
                Artisan::call('up');
                $this->addOutput("Exiting maintenance mode:", true);
            }
            $this->addOutput("Install complete");

            return $this->sendSuccess();
        } else {
            return $this->sendFailed("There was a problem during installation. Please try again later");
        }
    }

    public function refresh()
    {
        $this->dbService->migrateAndSeedUpdate();
        $this->appService->flushCache();
        Artisan::call('vendor:publish', [
            '--provider' => 'AdminUI\AdminUI\Provider',
            '--tag'      => 'adminui-public',
            '--force'    => true
        ]);
        return $this->sendSuccess("Site refreshed");
    }
}
