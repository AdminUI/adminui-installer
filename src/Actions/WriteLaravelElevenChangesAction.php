<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class WriteLaravelElevenChangesAction
{
    public function execute(): bool
    {
        $version = app()->version();
        $isEleven = version_compare($version, 11, ">=");
        if (!$isEleven) {
            return false;
        }

        $appFile = base_path('bootstrap/app.php');
        $contents = file_get_contents($appFile);

        if (empty($contents)) {
            throw new \Exception("Couldn't load local bootstrap/app.php file");
        }

        $remote = "https://raw.githubusercontent.com/laravel/laravel/11.x/bootstrap/app.php";
        $response = Http::get($remote);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Couldn't load remote bootstrap/app.php file");
        }
        $remoteContents = $response->getBody()->getContents();
        $remoteHash = md5($remoteContents);
        $localHash = md5($contents);

        if ($remoteHash !== $localHash) {
            throw new \Exception("Bootstrap/app.php file already modified, aborting overwrite");
        }


        $stub = file_get_contents(config('adminui-installer.root') . "/stubs/app.php.stub");
        file_put_contents($appFile, $stub);

        return true;
    }
}
