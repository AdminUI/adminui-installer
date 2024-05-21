<?php

namespace AdminUI\AdminUIInstaller\Actions;

use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Facades\Install;

class UpdatePackageFromUrlAction
{
    protected string $key;

    public function __construct()
    {
        $this->key = config('adminui-installer.licence');
    }

    public function execute(string $name, string $url, string $shasum, string $version)
    {
        $disk = Install::getDisk();
        $response = Http::accept('application/octet-stream')->withToken($this->key)->get($url);
        $relativeZipPath = $name . ".zip";

        if ($response->successful() === true) {
            $disk->put($relativeZipPath, $response->body());
        } else {
            throw new \Exception('Unable to download install file from AdminUI Server. Aborting install');
        }

        $zipPath = $disk->path($relativeZipPath);
        $archive = new ZipArchive;
        if ($archive->open($zipPath) !== true) {
            throw new \Exception('Unable to extract download package');
        }
        $extractPath = $disk->path($name);
        $archive->extractTo($extractPath);
        $archive->close();

        // Create a temporary storage disk to allow the use of the Storage class in the `/packages` directory
        /** @var Filesystem */
        $packages = Storage::build([
            'driver' => 'local',
            'root' => base_path('packages'),
        ]);

        if ($packages->exists($name . '/.git')) {
            $name = $name . '-test';
        }

        if ($packages->exists($name)) {
            $packages->deleteDirectory($name);
        }

        $packages->makeDirectory($name);
        $destinationPath = $packages->path($name);

        File::move($extractPath, $destinationPath);

        $realName = Str::replaceLast('-test', '', $name);

        \AdminUI\AdminUI\Models\Configuration::updateOrCreate(
            ['name' => 'installed_version_' . $realName],
            ['section' => 'private', 'type' => 'text', 'label' => 'Installed Version of ' . Str::headline($realName), 'value' => $version],
        );

        return true;
    }
}
