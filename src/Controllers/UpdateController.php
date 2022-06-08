<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Parsedown;
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
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

class UpdateController extends BaseInstallController
{
    public function checkUpdate(Request $request)
    {
        $Parsedown = new Parsedown();

        $installedVersion = \AdminUI\AdminUI\Models\Configuration::where('name', 'installed_version')->firstOrCreate(
            ['name'  => 'installed_version'],
            [
                'label' => 'Installed Version',
                'value' => 'v0.0.1',
                'section' => 'private',
                'type'  => 'text'
            ]
        );

        // Fetch the available version from the MGMT server
        $updateDetails = $this->checkLatestRelease(config('adminui.licence_key'));

        // Check if update is available
        $updateIsAvailable = version_compare($updateDetails['version'], $installedVersion->value, '>');
        $availableMajor = $this->getMajor($updateDetails['version']);
        $installedMajor = $this->getMajor($installedVersion->value);

        $isMajor = $availableMajor > $installedMajor;

        if (true === $updateIsAvailable) {
            $Parsedown = new Parsedown();

            $json = $updateDetails->json();
            $json['changelog'] = $Parsedown->text($json['changelog']);
            return $this->sendSuccess(['update' => $json, 'message' => 'There is a new version of AdminUI available!', 'isMajor' => $isMajor]);
        } else {
            $this->addOutput('You are already using the latest version of AdminUI');
            return $this->sendFailed();
        }
    }

    private function getMajor(string $version): int
    {
        return preg_match('/v?(\d+)\.(\d+)/', $version);
    }
}
