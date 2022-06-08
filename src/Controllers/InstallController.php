<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

class InstallController extends BaseInstallController
{

    public function index()
    {
        $isInstalled = $this->checkIfInstalled();

        if ($isInstalled) return view('adminui-installer::already-installed');
        else return view('adminui-installer::index');
    }

    /* ******************************************
     * STEP ONE
    ****************************************** */
    public function downloadInstaller(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string']
        ]);

        $isInstalled = $this->checkIfInstalled();
        if (true === $isInstalled) {
            $this->addOutput('AdminUI is already installed. Please use the update function from your installation instead');
            return $this->sendFailed();
        }

        // Test database connection
        $hasDbConnection = $this->checkDatabase();

        if (false === $hasDbConnection) {
            return $this->sendFailed();
        }

        $installDetails = $this->checkLatestRelease($validated['key']);
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
    public function extractInstaller(Request $request)
    {
        // Enter maintenance mode
        Artisan::call("down");
        $this->addOutput("Entering maintenance mode:", true);

        $zipPath = Storage::path($this->zipPath);

        $archive = new ZipArchive;
        if ($archive->open($zipPath) === true) {
            $this->installArchive($archive);

            Artisan::call("up");
            return $this->sendSuccess();
        } else {
            $this->addOutput("There was a problem extracting the installer. Please try again later");
            Artisan::call("up");
            return $this->sendFailed();
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

        $this->updateEnvironmentVariables($validated['key']);
        $this->updateComposerJson();
        sleep(1);
        $this->runComposerUpdate();
        sleep(1);

        $this->addOutput("Migrating Laravel framework", true);
        $this->flushCache();

        return $this->sendSuccess();
    }

    /* **************************************************
     * STEP FOUR - Publish Base Migrations
     ************************************************** */
    public function basePublish()
    {
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
        Artisan::call('down');
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
            Artisan::call("migrate --path=\"" . $migrationPath . "\"");
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
            '--tag'      => 'adminui-setup-only',
            '--force'    => true
        ]);
        $this->addOutput("Publishing setup:", true);

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

    /**
     * updateComposerJson - Adds required entries into the root composer.json file
     *
     * @return void
     */
    private function updateComposerJson()
    {
        $jsonRaw = file_get_contents(base_path('composer.json'));
        $json = json_decode($jsonRaw, true);

        if (!isset($json['repositories'])) {
            $json['repositories'] = [];
        }

        if (array_search('./packages/adminui', array_column($json['repositories'], 'url')) === false) {
            $json['repositories'][] = [
                "type" => "path",
                "url" => "./packages/adminui",
                "options" => [
                    "symlink" => true
                ]
            ];
        }

        if (!isset($json['require'])) {
            $json['require'] = [];
        }

        if (!isset($json['require']['adminui/adminui'])) {
            $json['require']['adminui/adminui'] = '@dev';
        }

        $newJsonRaw = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('composer.json'), $newJsonRaw);
    }

    /**
     * updateEnvironmentVariables - Adds required entries to the root .env files
     *
     * @param  string $licenceKey
     * @return void
     */
    private function updateEnvironmentVariables(string $licenceKey)
    {
        $inserts = [
            'ADMINUI_PREFIX'            => 'admin',
            'ADMINUI_LICENCE_ENDPOINT'  => 'https://management.adminui.co.uk/api/licence',
            'ADMINUI_LICENCE_KEY'       => $licenceKey,
            'ADMINUI_ADDRESS_ENDPOINT'  => 'https://management.adminui.co.uk/api/address',
            'ADMINUI_UPDATE_ENDPOINT'   => 'https://management.adminui.co.uk/api/update'
        ];

        foreach ($inserts as $key => $value) {
            $this->setEnvironmentValue($key, $value);
        }

        sleep(2);
        Artisan::call("cache:clear");
        Artisan::call("config:clear");
    }
}
