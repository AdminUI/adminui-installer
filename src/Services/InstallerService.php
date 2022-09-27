<?php

namespace AdminUI\AdminUIInstaller\Services;

use ZipArchive;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

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

    public function extract(string $relativePath): bool
    {
        $zipPath = Storage::path($relativePath);

        $archive = new ZipArchive;
        if ($archive->open($zipPath) === true) {
            return true;
        } else {
            throw new \Exception("Unable to extract download package");
        }
    }
}
