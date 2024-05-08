<?php

namespace AdminUI\AdminUIInstaller\Actions;

use AdminUI\AdminUIInstaller\Facades\Install;
use AdminUI\AdminUIInstaller\Facades\Json;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class UnpackReleaseAction
{
    public function execute()
    {
        $relativeZipPath = Install::getZipPath();
        $relativeExtractPath = Install::getExtractPath();
        $disk = Install::getDisk();
        $zipPath = $disk->path($relativeZipPath);

        $archive = new ZipArchive;
        if ($archive->open($zipPath) !== true) {
            throw new \Exception('Unable to extract download package');
        }

        $extractPath = $disk->path($relativeExtractPath);

        $archive->extractTo($extractPath);
        $archive->close();

        // Create a temporary storage disk to allow the use of the Storage class in the `/packages` directory
        /** @var Filesystem */
        $packages = Storage::build([
            'driver' => 'local',
            'root' => base_path('packages'),
        ]);

        $installDirectory = 'adminui';

        // If adminui is installed via GitHub, use a test install location - This can be deleted after testing
        if ($packages->exists($installDirectory.'/.git')) {
            $installDirectory = 'adminui-test';
        }

        // Delete the adminui packages directory if present
        if ($packages->exists($installDirectory)) {
            $packages->deleteDirectory($installDirectory);
        }

        // Create a new empty directory in the same location
        $packages->makeDirectory($installDirectory);
        $destinationPath = $packages->path($installDirectory);

        File::move($extractPath, $destinationPath);

        Json::setField(field: 'installSize', data: Install::getDirectorySize($destinationPath));
    }
}
