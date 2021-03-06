<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use ZipArchive;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\PhpExecutableFinder;

class BaseInstallController extends Controller
{
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
    /**
     * $output - Holds the log output from executing installs or updates
     *
     * @var array
     */
    protected $output = [];

    /**
     * checkLatestRelease - Contact MGMT for the latest AdminUI release
     *
     * @param  string $key - The client's licence key
     * @return Response|JsonResponse
     */
    protected function checkLatestRelease(string $key)
    {
        $response = Http::withToken($key)->get(config('adminui-installer.version_endpoint'));
        $response->onError(function () {
            $this->addOutput("Unable to fetch release information from MGMT. Aborting");
            return $this->sendFailed();
        });

        $this->addOutput("Latest available version is " . $response['version']);
        return $response;
    }

    /**
     * downloadPackage - Download the specified install package from MGMT and get download stats
     *
     * @param  string $key - The client's licence key
     * @param  string $url - The MGMT url for the required install package
     * @return bool|JsonResponse
     */
    protected function downloadPackage(string $key, string $url)
    {
        $response = Http::accept('application/octet-stream')->withToken($key)->get($url);
        if ($response->successful() === true) {
            Storage::put($this->zipPath, $response->body());
            $this->getDownloadStats($response);
            return true;
        } else {
            $this->addOutput("Unable to download install file from MGMT. Aborting install");
            return $this->sendFailed();
        }
    }

    /**
     * getDownloadStats - Get speed information about the download from MGMT
     *
     * @param  Response $package - The HTTP response from the MGMT download request
     * @return void
     */
    protected function getDownloadStats(Response $package)
    {
        $stats = $package->handlerStats();

        $statsSize = $this->convertToReadableSize($stats['size_download']);
        $statsTime = round($stats['total_time'], 1) . " seconds";
        $statsSpeed = $this->convertToReadableSize($stats['speed_download']) . "/s";
        $statsSummary = "Downloaded {$statsSize} in {$statsTime} @ {$statsSpeed}";
        $this->addOutput($statsSummary);
    }

    /**
     * validatePackage - Check saved .zip installer against the initial checksum for the download
     *
     * @param  string $shasum
     * @return boolean|JsonResponse
     */
    protected function validatePackage(string $shasum)
    {
        $zipIsValid = $this->fileChecksum($shasum);

        if (false === $zipIsValid) {
            $this->addOutput("Could not confirm authenticity of AdminUI package. Aborting");
            return $this->sendFailed();
        } else {
            $this->addOutput("Validating checksum: package is valid");
            return true;
        }
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
        $this->addOutput("Extracted size is " . $this->getDirectorySize($absoluteDestination));
        $this->addOutput("Moved extracted files");
        return $absoluteDestination;
    }

    /**
     * addOutput - Adds an entry into the install log
     *
     * @param  string $intro - The text to put at the beginning of the output line
     * @param  bool $artisan - If true, the output from the last Artisan::call will be appended
     * @param  string $logData - Any additional data to log
     * @return void
     */
    protected function addOutput($intro = "", $artisan = false, $logData = "")
    {
        $line = $intro;
        if (true === $artisan) {
            $line .= " " . $this->cleanOutput(Artisan::output());
        }
        if (!empty($logData)) {
            $line .= " " . $this->cleanOutput($logData);
        }
        $this->output[] = $line;
    }

    /**
     * cleanOutput - Concatenates multiple lines into one for readability
     *
     * @param  string $output
     * @return string
     */
    private function cleanOutput(String $output)
    {
        return str_replace(PHP_EOL, ' ', $output);
    }

    /**
     * checkIfInstalled - verify that AdminUI is installed on the system
     *
     * @return boolean
     */
    protected function checkIfInstalled()
    {
        return class_exists('\AdminUI\AdminUI\Provider');
    }

    /**
     * cleanUpdateDirectory - Makes sure the temporary install directory is empty
     *
     * @return void
     */
    protected function cleanUpdateDirectory(): void
    {
        $this->addOutput("Cleaning temporary update directory...");
        if (Storage::exists($this->zipPath)) {
            Storage::delete($this->zipPath);
            $this->addOutput("Deleted previous .zip file");
        }
        if (Storage::exists($this->extractPath)) {
            Storage::deleteDirectory($this->extractPath);
            $this->addOutput("Deleted previously extracted archive");
        }
        $this->addOutput("Temporary update directory cleaning complete");
    }

    protected function getDirectorySize($path)
    {
        $bytesTotal = 0;
        $path = realpath($path);
        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytesTotal += $object->getSize();
            }
        }
        return $this->convertToReadableSize($bytesTotal);
    }

    /**
     * convertToReadableSize
     *
     * @param  int|float $size - File size in bytes
     * @return string - Human-readable file size
     */
    protected function convertToReadableSize($size)
    {
        $base = log($size) / log(1024);
        $suffix = array("B", "KB", "MB", "GB", "TB");
        $f_base = floor($base);
        return round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];
    }

    protected function flushCache()
    {
        $this->addOutput("Flushing cache...");
        Artisan::call('optimize:clear');
        $this->addOutput("Output:", true);

        Artisan::call('optimize');
        $this->addOutput("Site optimisation complete");
    }

    protected function runComposerUpdate()
    {
        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();
        $composerPath = config()->get('adminui-installer.base_path') . '/lib/composer.phar';

        if (file_exists($composerPath) === false) {
            $this->addOutput("Unable to find composer.phar. Looking for " . $composerPath);
            return $this->sendFailed();
        }

        $process = new Process([$phpBinaryPath, config('adminui-installer.base_path') . '/lib/composer.phar', "update", "--no-interaction", "--no-scripts"], null, ["PATH" => '$PATH:/usr/local/bin']);
        $process->setTimeout(300);
        $process->setWorkingDirectory(base_path());
        $process->run();

        if ($process->isSuccessful()) {
            $this->addOutput("Composer update complete", false, $process->getOutput());
        } else {
            $this->addOutput("Composer error:", false, $process->getErrorOutput());
            return $this->sendFailed();
        }
    }

    protected function setEnvironmentValue($key, $value)
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

    protected function updateVersionEntry(string $version)
    {
        $version = \AdminUI\AdminUI\Models\Configuration::firstOrCreate(
            ['name' => 'installed_version'],
            ['section'  => 'private', 'type' => 'text', 'label' => 'Installed Version', 'value' => $version],
        );
    }

    protected function checkDatabase()
    {
        try {
            DB::select('SHOW TABLES');
            return true;
        } catch (\Exception $e) {
            $this->addOutput("Unable to connect to database. Please check your database settings and try again.");
            return false;
        }
    }

    protected function sendSuccess($data = null)
    {
        return response()->json([
            'status' => 'success',
            'log'   => $this->output,
            'data'  => $data
        ]);
    }

    protected function sendFailed()
    {
        return response()->json([
            'status' => 'failed',
            'log'   => $this->output
        ]);
    }

    protected function hashLockFileContents(string $root)
    {
        $path = $root . "/composer.json";
        return hash_file('sha256', $path);
    }
}
