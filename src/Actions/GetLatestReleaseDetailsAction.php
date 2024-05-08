<?php

namespace AdminUI\AdminUIInstaller\Actions;

use AdminUI\AdminUIInstaller\Facades\Json;
use Illuminate\Support\Facades\Http;

class GetLatestReleaseDetailsAction
{
    protected string $key;

    public function __construct()
    {
        $this->key = config('adminui-installer.licence');
    }

    public function execute()
    {
        $response = Http::acceptJson()->withToken($this->key)->get(config('adminui-installer.version_endpoint'));
        $response->onError(function () use ($response) {
            if ($response->status() === 401) {
                throw new \Exception('This licence key is invalid. Check your credentials and try again');
            } elseif ($response->status() === 403) {
                throw new \Exception('There was a problem with your account. Please contact AdminUI Support.');
            } else {
                throw new \Exception('Unable to fetch release information from AdminUI Server. Aborting');
            }
        });

        $releaseDetails = $response->json();
        Json::setField('releaseDetails', $releaseDetails);

        return $releaseDetails;
    }
}
