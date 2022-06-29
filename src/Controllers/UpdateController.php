<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Parsedown;
use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

class UpdateController extends BaseInstallController
{
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
            $this->addOutput('Can\'t update this copy of AdminUI since it appears to be outside the packages folder');
            return $this->sendFailed();
        }

        // Fetch the available version from the MGMT server
        $updateDetails = $this->checkLatestRelease(config('adminui.licence_key'));

        // Check if update is available
        $updateIsAvailable = version_compare($updateDetails['version'], $installedVersion->value, '>');

        // Calculate if this is a major update for the purpose of warning the user
        $availableMajor = $this->getMajor($updateDetails['version']);
        $installedMajor = $this->getMajor($installedVersion->value);
        $isMajor = $availableMajor > $installedMajor;

        if (true === $updateIsAvailable) {
            // Parse the .md format changelog into HTML
            $Parsedown = new Parsedown();
            $json = $updateDetails->json();
            $json['changelog'] = $Parsedown->text($json['changelog']);

            return $this->sendSuccess(['update' => $json, 'message' => 'There is a new version of AdminUI available!', 'isMajor' => $isMajor]);
        } else {
            $this->addOutput('You are already using the latest version of AdminUI');
            return $this->sendFailed();
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
        $validated = $request->validate([
            'url'   => ['required', 'url'],
            'version' => ['required', 'string'],
            'shasum'    => ['required', 'string']
        ]);

        $this->cleanUpdateDirectory();
        $this->downloadPackage(config('adminui.licence_key'), $validated['url']);
        $this->validatePackage($validated['shasum']);

        Artisan::call('down', [
            '--render' => 'adminui-installer::maintenance'
        ]);
        $this->addOutput("Entering maintenance mode:", true);

        $zipPath = Storage::path($this->zipPath);

        $archive = new ZipArchive;
        if ($archive->open($zipPath) === true) {
            $this->addOutput("Extract complete");

            $absoluteDestination = $this->installArchive($archive);
            $this->checkForComposerUpdate($absoluteDestination);

            $this->migrateAndSeedUpdate();
            Artisan::call('vendor:publish', [
                '--provider' => 'AdminUI\AdminUI\Provider',
                '--tag'      => 'adminui-public',
                '--force'    => true
            ]);
            $this->addOutput("Output:", true);
            $this->flushCache();

            // Update the installed version in the database configurations table
            $version = \AdminUI\AdminUI\Models\Configuration::where('name', 'installed_version')->first();
            $version->value = $validated['version'];
            $version->save();

            Artisan::call('up');
            $this->addOutput("Exiting maintenance mode:", true);
            $this->addOutput("Install complete");

            return $this->sendSuccess();
        } else {
            $this->addOutput("There was a problem during installation. Please try again later");
            return $this->sendFailed();
        }
    }

    /**
     * migrateAndSeedUpdate - Runs the required migration and seed paths for updating AdminUI
     *
     * @return void
     */
    private function migrateAndSeedUpdate()
    {
        sleep(1);

        // Migrate any db updates
        $this->addOutput("Running DB migrations");
        Artisan::call('migrate', [
            '--force' => true
        ]);
        $this->addOutput("Output:", true);

        // Update database seeds
        // Update adminui navigation seeds
        $this->addOutput("Running AdminUI seeders");
        Artisan::call('db:seed', [
            '--class' => 'AdminUI\AdminUI\Database\Seeds\DatabaseSeederUpdate',
            '--force' => true
        ]);
        $this->addOutput("Output:", true);

        //  Frontend site specific seeds
        if (file_exists(base_path('database/seeders/AdminUIUpdateSeeder.php'))) {
            $this->addOutput("Running DB update seed");
            Artisan::call('db:seed', [
                '--class' => 'Database\Seeders\AdminUIUpdateSeeder',
            ]);
            $this->addOutput("Output:", true);
        }
    }

    public function refresh()
    {
        $this->migrateAndSeedUpdate();
        Artisan::call('optimize:clear');
        Artisan::call('optimize');
        return $this->sendSuccess("Site refreshed");
    }

    private function checkForComposerUpdate($packageLocation)
    {
        $updateHash = $this->hashLockFileContents($packageLocation);
        $installedHash = \AdminUI\AdminUI\Models\Configuration::where('name', 'installed_composer_hash')->firstOrCreate(
            ['name'  => 'installed_composer_hash'],
            [
                'label' => 'Composer JSON file hash',
                'value' => '',
                'section' => 'private',
                'type'  => 'text'
            ]
        );

        if ($updateHash !== $installedHash) {
            $this->runComposerUpdate();
            $installedHash->value = $updateHash;
            $installedHash->save();
        }
    }
}
