<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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

        // Test database connection
        $hasDbConnection = $this->databaseService->check();

        // if no database connection
        if (false === $hasDbConnection) {
            return view('adminui-installer::no-database');
        }

        // if already installed
        if ($isInstalled) {
            return view('adminui-installer::already-installed');
        }
        // show the installer
        return view('adminui-installer::index');
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

        $this->downloadPackage($validated['key'], $installDetails['url']);

        $packageIsValid = $this->validatePackage($installDetails['shasum']);

        if ($packageIsValid) {
            return response()->json([
                'status' => 'success',
                'log'   => $this->output,
                'data'  => [
                    'version'   => $installDetails['version']
                ]
            ]);
        }
    }

    /* ******************************************
     * STEP TWO
    ****************************************** */
    public function extractInstaller()
    {
        try {
            $result = $this->installerService->extract($this->zipPath);
            $this->addOutput("Successfully extracted download package");
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
            $this->runComposerUpdate();
            $this->addOutput("Successfully updated dependencies");
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }
        // $this->flushCache();

        return $this->sendSuccess();
    }

    /* **************************************************
     * STEP FOUR - Publish Base Migrations
     ************************************************** */
    public function basePublish()
    {
        Artisan::call('vendor:publish', [
            '--provider' => 'AdminUI\AdminUI\Provider',
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
        Artisan::call('down', [
            '--render' => 'adminui-installer::maintenance'
        ]);

        $baseDir = Storage::build([
            'driver' => 'local',
            'root' => base_path('')
        ]);

        // This will update adminui vue and styling components
        $this->addOutput("Publishing resources...");

        sleep(2);

        $this->addOutput("Publishing Spatie/Permissions:", true);
        if (Schema::hasTable('permissions') === false) {
            $path = base_path("database/migrations");
            $files = File::allFiles($path);
            $found = array_filter($files, function ($v, $k) {
                return preg_match("/create_permission_tables.php$/", $v);
            }, ARRAY_FILTER_USE_BOTH);
            $foundFlat = array_merge($found);
            $migrationPath = "database/migrations/" . $foundFlat[0]->getFilename();
            $updatedMigrationPath = preg_replace('/\d{4}_\d{2}_\d{2}/', '2000_01_01', $migrationPath);
            $baseDir->move($migrationPath, $updatedMigrationPath);
            sleep(1);
            Artisan::call("migrate --path=\"" . $updatedMigrationPath . "\"");
            $this->addOutput("Framework migrate:", true);
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
            '--provider' => 'AdminUI\AdminUI\Provider',
            '--tag'      => 'adminui-public',
            '--force'    => true
        ]);
        $this->addOutput("Publishing public:", true);

        Artisan::call('vendor:publish', [
            '--provider' => 'AdminUI\AdminUI\Provider',
            '--tag'      => 'adminui-config',
            '--force'    => true
        ]);
        $this->addOutput("Publishing config:", true);

        $this->flushCache();
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

        Artisan::call('migrate');
        sleep(1);

        if (Storage::exists('media') === false) {
            Storage::makeDirectory('media');
        }

        Artisan::call('storage:link');

        if (!Schema::hasTable('sessions')) {
            Artisan::call('session:table');
        }
        if (!Schema::hasTable('jobs')) {
            Artisan::call('queue:table');
        }

        $dbSeeder = new \AdminUI\AdminUI\Database\Seeds\DatabaseSeeder();
        $dbSeeder->run();
        // Update the installed version in the database configurations table
        $this->updateVersionEntry($validated['version']);

        // Keep track of each setup run file
        $setup = new \AdminUI\AdminUI\Models\Setup();
        $setup->package = 'AdminUI';
        $setup->save();

        $this->addOutput("Exiting maintenance mode:", true);
        $this->addOutput("Install complete");

        return $this->sendSuccess();
    }
}
