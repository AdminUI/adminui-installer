<?php

namespace AdminUI\AdminUIInstaller\Actions;

use AdminUI\AdminUIInstaller\Facades\Install;
use AdminUI\AdminUIInstaller\Facades\Json;
use Illuminate\Support\Facades\Http;

class DownloadLatestReleaseAction
{
    protected string $key;

    public function __construct()
    {
        $this->key = config('adminui-installer.licence');
    }

    public function execute()
    {
        $zipPath = Install::getZipPath();
        $releaseDetails = Json::getField('releaseDetails');

        if (empty($releaseDetails) || empty($releaseDetails['url'])) {
            throw new \Exception("Couldn't find valid release URL for download");
        }
        $url = $releaseDetails['url'];

        $response = Http::accept('application/octet-stream')->withToken($this->key)->get($url);
        if ($response->successful() === true) {
            $disk = Install::getDisk();
            $disk->put($zipPath, $response->body());
            $stats = Install::getDownloadStats($response);
            Json::setField('downloadStats', $stats);
        } else {
            throw new \Exception('Unable to download install file from AdminUI Server. Aborting install');
        }
    }
}
