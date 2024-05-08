<?php

namespace AdminUI\AdminUIInstaller\Services;

use FilesystemIterator;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class InstallService
{
    /**
     * $zipPath - Path to use for the .zip installer relative to default Storage
     *
     * @var string
     */
    protected $zipPath = 'adminui-installer.zip';

    protected $extractPath = 'adminui-installer';

    protected FilesystemAdapter $disk;

    public function __construct()
    {
        $this->disk = Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/adminui-installer'),
        ]);
    }

    public function getDisk(): FilesystemAdapter
    {
        return $this->disk;
    }

    public function getZipPath(): string
    {
        return $this->zipPath;
    }

    public function getExtractPath(): string
    {
        return $this->extractPath;
    }

    public function isInstalled(): bool
    {
        return class_exists('\AdminUI\AdminUI\Provider');
    }

    /**
     * checkIfMigrated - verify that AdminUI migration has been run
     */
    public function isMigrated(): bool
    {
        return Schema::hasTable('permissions') && Schema::hasTable('admins');
    }

    /**
     * getDownloadStats - Get speed information about the download from MGMT
     *
     * @param  Response  $package  - The HTTP response from the MGMT download request
     * @return void
     */
    public function getDownloadStats(Response $package): string
    {
        $stats = $package->handlerStats();

        $statsSize = $this->convertToReadableSize($stats['size_download']);
        $statsTime = round($stats['total_time'], 1).' seconds';
        $statsSpeed = $this->convertToReadableSize($stats['speed_download']).'/s';
        $statsSummary = "Downloaded {$statsSize} in {$statsTime} @ {$statsSpeed}";

        return $statsSummary;
    }

    /**
     * convertToReadableSize
     *
     * @param  int|float  $size  - File size in bytes
     * @return string - Human-readable file size
     */
    public function convertToReadableSize($size): string
    {
        $base = log($size) / log(1024);
        $suffix = ['B', 'KB', 'MB', 'GB', 'TB'];
        $f_base = floor($base);

        return round(pow(1024, $base - floor($base)), 1).$suffix[$f_base];
    }

    public function getDirectorySize($path): string
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
}
