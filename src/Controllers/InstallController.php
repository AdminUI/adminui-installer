<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use ZipArchive;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Services\DatabaseService;
use AdminUI\AdminUIInstaller\Services\InstallerService;
use AdminUI\AdminUIInstaller\Services\ApplicationService;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

class InstallController extends BaseInstallController
{
    public function __construct(
        protected InstallerService $installerService,
        protected DatabaseService $databaseService,
        protected ApplicationService $appService
    ) {
    }

    public function index()
    {

        $isInstalled = $this->installerService->checkIfInstalled();
        $isMigrated = $this->installerService->checkIfMigrated();

        // Test database connection
        $hasDbConnection = $this->databaseService->check();

        // if no database connection
        if (false === $hasDbConnection) {
            return view('adminui-installer::no-database');
        }

        // if already installed
        if ($isInstalled && $isMigrated) {
            return view('adminui-installer::already-installed');
        }
        // show the installer
        return view('adminui-installer::index', [
            'isInstalled' => $isInstalled,
            'isMigrated' => $isMigrated
        ]);
    }

    /* ******************************************
     * STEP ONE
    ****************************************** */
    public function downloadInstaller(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string']
        ]);

        $isInstalled = $this->installerService->checkIfInstalled();

        if (true === $isInstalled) {
            return $this->sendFailed('AdminUI is already installed. Please use the update function from your installation instead');
        }

        // Test database connection
        $hasDbConnection = $this->databaseService->check();

        if (false === $hasDbConnection) {
            return $this->sendFailed("No database connection available. Check your DB settings and try again");
        }

        try {
            $installDetails = $this->installerService->checkLatestRelease($validated['key']);
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }

        $this->addOutput("Latest available version is " . $installDetails['version']);

        try {
            $summary = $this->installerService->downloadPackage($validated['key'], $installDetails['url'], $this->zipPath);
            $this->addOutput($summary);
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }

        $packageIsValid = $this->installerService->validatePackage($installDetails['shasum'], $this->zipPath);

        if ($packageIsValid) {
            return $this->sendSuccess([
                'version'   => $installDetails['version']
            ]);
        } else {
            return $this->sendFailed("Unable to validate installer package");
        }
    }

    /* ******************************************
     * STEP TWO
    ****************************************** */
    public function extractInstaller()
    {
        try {
            $result = $this->installerService->extract($this->zipPath, $this->extractPath);
            $this->addOutput("Successfully extracted download package", false, $result['size']);
            return $this->sendSuccess();
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }
    }

    /* ******************************************
     * STEP THREE
    ****************************************** */
    public function updateComposer(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
        ]);

        try {
            $this->appService->updateEnvironmentVariables($validated['key']);
            $this->addOutput("Updated .env file");
        } catch (\Exception $e) {
            return $this->sendFailed("There was a problem updating your environment variables");
        }
        try {
            $this->appService->updateComposerJson();
            $this->addOutput("Added AdminUI to composer.json file");
        } catch (\Exception $e) {
            return $this->sendFailed("There was a problem installing AdminUI into your application");
        }

        try {
            $output = $this->appService->composerUpdate();
            $this->addOutput("Successfully updated dependencies", true, $output);
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }

        return $this->sendSuccess();
    }

    /* **************************************************
     * STEP THREE POINT FIVE - Flush cache
     ************************************************** */
    public function clearCache()
    {
        sleep(5);
        $result = $this->appService->flushCache();
        $this->addOutput("All cache cleared:", true, $this->cleanOutput($result));
        return $this->sendSuccess();
    }

    /* **************************************************
     * STEP FOUR - Publish Base Migrations
     ************************************************** */
    public function basePublish()
    {
        Artisan::call('vendor:publish', [
            '--tag'      => 'adminui-setup-only',
            '--force'    => true
        ]);
        $this->addOutput("Publishing setup:", true);

        Artisan::call('vendor:publish', [
            '--provider' => 'Spatie\Permission\PermissionServiceProvider',
            '--force'    => true
        ]);
        $this->addOutput("Spatie published:", true);
        Artisan::call("optimize:clear");
        $this->addOutput("All cache cleared:", true);
        return $this->sendSuccess();
    }

    /* **************************************************
     * STEP FIVE - Run base migrations
     ************************************************** */

    public function baseMigrations()
    {
        $this->appService->down();

        $this->addOutput("Publishing Spatie/Permissions");

        try {
            $this->databaseService->spatieMigrations();
            Artisan::call("optimize:clear");
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }

        Artisan::call('up');

        return $this->sendSuccess();
    }

    /* ******************************************
     * STEP SIX - Publish AdminUI Resources
    ****************************************** */
    public function publish()
    {

        Artisan::call('vendor:publish', [
            '--tag'      => 'adminui-public',
            '--force'    => true
        ]);
        $this->addOutput("Publishing public:", true);

        $this->appService->flushCache();
        return $this->sendSuccess();
    }

    /* ******************************************
     * STEP SEVEN
    ****************************************** */
    public function finishInstall(Request $request)
    {
        $validated = $request->validate([
            'version'   => ['required', 'string']
        ]);

        try {
            $this->installerService->finish($validated);
            $this->addOutput("Install complete");
            return $this->sendSuccess();
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }
    }
}
