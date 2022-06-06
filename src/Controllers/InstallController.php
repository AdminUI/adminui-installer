<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\PhpExecutableFinder;

class InstallController extends Controller
{
    private $zipPath = 'adminui-installer.zip';
    private $extractPath = 'adminui-installer';
    private $output = [];

    public function index()
    {
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

        /* $auiSetup = new \AdminUI\AdminUI\Setup\SetupController;
        $auiSetup->seed();
        dd("done"); */

        $isInstalled = $this->checkIfInstalled();
        if (true === $isInstalled) {
            $this->addOutput('AdminUI is already installed. Please use the update function from your installation instead');
            return response()->json([
                'status'    => 'failed',
                'log'       => $this->output
            ]);
        }

        $this->addOutput("Starting install of AdminUI...");

        $this->addOutput("Downloading install package...");
        $installDetails = Http::withToken($validated['key'])->get(config('adminui-installer.version_endpoint'));
        $package = Http::accept('application/octet-stream')->withToken($validated['key'])->get($installDetails['url']);


        $wasSuccessful = Storage::put($this->zipPath, $package->body());
        $this->addOutput("Install package downloaded");

        $zipIsValid = $this->fileChecksum($installDetails['shasum']);

        if (false === $zipIsValid) {
            $this->addOutput("Could not confirm authenticity of AdminUI install package. Aborting install");
            return response()->json([
                'status'    => 'failed',
                'log'   => $this->output,
            ]);
        } else {
            $this->addOutput("Validating checksum: Install file is valid");
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
            $this->addOutput("Extract complete");

            $this->installArchive($archive);

            Artisan::call("up");
            return response()->json([
                'status' => 'success',
                'log'   => $this->output
            ]);
        } else {
            $this->addOutput("There was a problem during installation. Please try again later");
            Artisan::call("up");
            return response()->json([
                'status'    => 'failed',
                'log'       => $this->output
            ]);
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

        return response()->json([
            'status' => 'success',
            'log'   => $this->output
        ]);
    }

    /* ******************************************
     * STEP FOUR
    ****************************************** */
    public function publish()
    {
        $this->publishResources();
        $this->flushCache();
        return response()->json([
            'status' => 'success',
            'log'   => $this->output
        ]);
    }

    /* ******************************************
     * STEP FIVE
    ****************************************** */
    public function finishInstall(Request $request)
    {
        $validated = $request->validate([
            'version'   => ['required', 'string']
        ]);

        Artisan::call("down");

        $this->migrateAndSeed();
        // Update the installed version in the database configurations table
        $version = \AdminUI\AdminUI\Models\Configuration::firstOrCreate(
            ['name' => 'installed_version'],
            ['section'  => 'private', 'type' => 'text', 'label' => 'Installed Version', 'value' => ''],
        );
        $version->value = $validated['version'];
        $version->save();

        Artisan::call('up');
        $this->addOutput("Exiting maintenance mode:", true);
        $this->addOutput("Install complete");

        return response()->json([
            'status' => 'success',
            'log'   => $this->output
        ]);
    }

    protected function checkIfInstalled()
    {
        return class_exists('\AdminUI\AdminUI\Provider') === true;
    }

    /**
     * fileChecksum - Ensures that the file on the MGMT server is the same one that was saved to the local temp directory
     *
     * @param  string $checksum - The .zip checksum that was returned from the MGMT server
     * @return bool - is the file valid?
     */
    protected function fileChecksum(string $checksum)
    {
        return !empty($checksum) && $checksum === hash_file('sha256', Storage::path($this->zipPath));
    }

    /**
     * addOutput - Adds an entry into the install log
     *
     * @param  string $intro - The text to put at the beginning of the output line
     * @param  bool $artisan - If true, the output from the last Artisan::call will be appended
     * @param  string $logData - Any additonal data to log
     * @return void
     */
    private function addOutput($intro = "", $artisan = false, $logData = "")
    {
        $line = $intro;
        if (true === $artisan || !empty($logData)) {
            $line .= " " . $this->cleanOutput(Artisan::output()) . $logData ? $this->cleanOutput($logData) : "";
        }
        $this->output[] = $line;
    }

    private function cleanOutput(String $output)
    {
        return str_replace(PHP_EOL, ' ', $output);
    }

    /**
     * installArchive - Extract the install file and copy its contents to the /packages/adminui directory
     *
     * @param  ZipArchive $archive - The install .zip file from the temp dir loaded into a ZipArchive instance
     * @return void
     */
    protected function installArchive(ZipArchive $archive)
    {
        $extractedPath = Storage::path($this->extractPath);

        $archive->extractTo($extractedPath);
        $archive->close();

        // Create a temporary storage disk to allow the use of the Storage class in the `/packages` directory
        $packages = Storage::build([
            'driver' => 'local',
            'root' => base_path('packages'),
        ]);

        $installDirectory = 'adminui';

        // If adminui is installed via GitHub, use a test install location - This can be deleted after testing
        if ($packages->exists($installDirectory . "/.git")) {
            $installDirectory = 'adminui-test';
        }

        // Delete the adminui packages directory if present
        if ($packages->exists($installDirectory)) {
            $packages->deleteDirectory($installDirectory);
        }

        // Create a new empty directory in the same location 
        $packages->makeDirectory($installDirectory);

        $absoluteDestination = $packages->path($installDirectory);
        $absoluteSource = $extractedPath;

        // Move the extracted files from the Storage disk to the Packages disk
        File::move($absoluteSource, $absoluteDestination);
        $this->addOutput("Moved extracted files");
    }

    private function migrateAndSeed()
    {
        // Allows us to improve software response time a few years down the line
        sleep(2);

        // Migrate any db updates
        $this->addOutput("Running DB migrations");
        Artisan::call('migrate');
        $this->addOutput("Output:", true);

        // Update database seeds
        // Update adminui navigation seeds

        $this->addOutput("Running DB navigation seed");
        Artisan::call('db:seed', [
            '--class' => 'AdminUI\AdminUI\Database\Seeds\NavigationTableSeeder',
        ]);
        $this->addOutput("Output:", true);

        if (file_exists(base_path('database/seeders/AdminUIUpdateSeeder.php'))) {
            $this->addOutput("Running DB update seed");
            Artisan::call('db:seed', [
                '--class' => 'Database\Seeders\AdminUIUpdateSeeder',
            ]);
            $this->addOutput("Output:", true);
        }
    }

    private function publishResources()
    {

        Artisan::call("optimize:clear");
        sleep(2);
        // This will update adminui vue and styling components
        $this->addOutput("Publishing resources...");

        Artisan::call('vendor:publish', [
            '--provider' => 'Spatie\Permission\PermissionServiceProvider',
            '--force'    => true
        ]);
        $this->addOutput("Publishing Spatie/Permissions:", true);
        sleep(2);
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
            sleep(2);
        }

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
    }

    private function flushCache()
    {
        $this->addOutput("Flushing cache...");
        Artisan::call('optimize:clear');
        $this->addOutput("Output:", true);

        Artisan::call('optimize');
        $this->addOutput("Site optimisation complete");
    }

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

    private function runComposerUpdate()
    {
        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        $process = new Process(["composer", "update"], null, ["PATH" => '$PATH:' . $phpBinaryPath . ":/usr/local/bin"]);
        $process->setWorkingDirectory(base_path());
        $process->run();

        if ($process->isSuccessful()) {
            $this->addOutput("Composer update complete", false, $process->getOutput());
        } else {
            $this->addOutput("Composer error:", false, $process->getErrorOutput());
        }
    }

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

    public function setEnvironmentValue($key, $value)
    {
        $path = app()->environmentFilePath();

        if (file_exists($path)) {
            if (getenv($key)) {
                //replace variable if key exit
                file_put_contents($path, str_replace(
                    "$key=" . getenv($key),
                    "$key=" . '"' . $value . '"',
                    file_get_contents($path)
                ));
            } else {
                //set if variable key not exit
                $file   = file($path);
                $file[] = PHP_EOL . "$key=" . '"' . $value . '"';
                file_put_contents($path, $file);
            }
        }
    }
}
