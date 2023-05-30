<?php

namespace AdminUI\AdminUIInstaller\Services;

use ZipArchive;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;

class InstallerService
{
    /**
     * checkIfInstalled - verify that AdminUI is installed on the system
     *
     * @return boolean
     */
    public function checkIfInstalled()
    {
        return class_exists('\AdminUI\AdminUI\Provider');
    }

    /**
     * checkIfMigrated - verify that AdminUI migration has been run
     */
    public function checkIfMigrated(): bool
    {
        return Schema::hasTable('permissions') && Schema::hasTable('admins');
    }

    /**
     * checkLatestRelease - Contact MGMT for the latest AdminUI release
     *
     * @param  string $key - The client's licence key
     * @return Response|JsonResponse
     */
    public function checkLatestRelease(string $key): array
    {
        $response = Http::acceptJson()->withToken($key)->get(config('adminui-installer.version_endpoint'));
        $response->onError(function () use ($response) {
            if ($response->status() === 401) {
                throw new \Exception("This licence key is invalid. Check your credentials and try again");
            } else if ($response->status() === 403) {
                throw new \Exception("There was a problem with your account. Please contact AdminUI Support.");
            } else {
                throw new \Exception("Unable to fetch release information from AdminUI Server. Aborting");
            }
        });

        return $response->json();
    }

    /**
     * downloadPackage - Download the specified install package from MGMT and get download stats
     *
     * @param  string $key - The client's licence key
     * @param  string $url - The MGMT url for the required install package
     * @return bool|JsonResponse
     */
    public function downloadPackage(string $key, string $url, string $zipPath)
    {
        $response = Http::accept('application/octet-stream')->withToken($key)->get($url);
        if (true === $response->successful()) {
            Storage::put($zipPath, $response->body());
            return $this->getDownloadStats($response);
        } else {
            throw new \Exception("Unable to download install file from AdminUI Server. Aborting install");
        }
    }

    public function extract(string $relativePath, string $extractPath): array
    {
        $zipPath = Storage::path($relativePath);

        $archive = new ZipArchive;
        if ($archive->open($zipPath) === true) {
            return $this->installArchive($archive, $extractPath);
        } else {
            throw new \Exception("Unable to extract download package");
        }
    }

    /**
     * validatePackage - Check saved .zip installer against the initial checksum for the download
     *
     * @param  string $shasum
     * @return boolean|JsonResponse
     */
    public function validatePackage(string $shasum, string $zipPath): bool
    {
        $zipIsValid = $this->fileChecksum($shasum, $zipPath);

        if (false === $zipIsValid) {
            throw new \Exception("Could not confirm authenticity of AdminUI package. Aborting");
        }

        return true;
    }

    public function finish(array $data)
    {
        Artisan::call('migrate');
        sleep(1);

        if (Storage::exists('media') === false) {
            Storage::makeDirectory('media');
        }

        Artisan::call('storage:link');

        if (!Schema::hasTable('jobs')) {
            Artisan::call('queue:table');
            Artisan::call('migrate');
        }

        $dbSeeder = new \AdminUI\AdminUI\Database\Seeds\DatabaseSeeder;
        $dbSeeder->run();
        // Update the installed version in the database configurations table
        $this->updateVersionEntry($data['version']);

        return true;
    }

    /**
     * installArchive - Extract the install file and copy its contents to the /packages/adminui directory
     *
     * @param  ZipArchive $archive - The install .zip file from the temp dir loaded into a ZipArchive instance
     * @param string $extractTo - The extract path
     * @return array - The file size of the installed directory
     */
    public function installArchive(ZipArchive $archive, string $extractTo): array
    {
        $extractedPath = Storage::path($extractTo);

        $archive->extractTo($extractedPath);
        $archive->close();

        // Create a temporary storage disk to allow the use of the Storage class in the `/packages` directory
        /** @var Filesystem */
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
        return ['size' => $this->getDirectorySize($absoluteDestination), 'destination' => $absoluteDestination];
    }

    /* *********************************************
     * Internal Functions
     * ******************************************* */

    protected function getDirectorySize($path): string
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
     * fileChecksum - Ensures that the file on the MGMT server is the same one that was saved to the local temp directory
     *
     * @param  string $checksum - The .zip checksum that was returned from the MGMT server
     * @return bool - is the file valid?
     */
    protected function fileChecksum(string $checksum, string $zipPath): bool
    {
        return !empty($checksum) && $checksum === hash_file('sha256', Storage::path($zipPath));
    }

    /**
     * getDownloadStats - Get speed information about the download from MGMT
     *
     * @param  Response $package - The HTTP response from the MGMT download request
     * @return void
     */
    protected function getDownloadStats(Response $package): string
    {
        $stats = $package->handlerStats();

        $statsSize = $this->convertToReadableSize($stats['size_download']);
        $statsTime = round($stats['total_time'], 1) . " seconds";
        $statsSpeed = $this->convertToReadableSize($stats['speed_download']) . "/s";
        $statsSummary = "Downloaded {$statsSize} in {$statsTime} @ {$statsSpeed}";
        return $statsSummary;
    }

    /**
     * convertToReadableSize
     *
     * @param  int|float $size - File size in bytes
     * @return string - Human-readable file size
     */
    protected function convertToReadableSize($size): string
    {
        $base = log($size) / log(1024);
        $suffix = array("B", "KB", "MB", "GB", "TB");
        $f_base = floor($base);
        return round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];
    }

    public function updateVersionEntry(string $version = "v0.0.1")
    {
        return \AdminUI\AdminUI\Models\Configuration::updateOrCreate(
            ['name' => 'installed_version'],
            ['section'  => 'private', 'type' => 'text', 'label' => 'Installed Version', 'value' => $version],
        );
    }
}
