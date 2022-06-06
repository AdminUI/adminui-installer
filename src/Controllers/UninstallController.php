<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;


class UninstallController extends BaseInstallController
{

    public function index()
    {
        $installDirectory = 'adminui';
        $packages = Storage::build([
            'driver' => 'local',
            'root' => base_path('packages'),
        ]);

        $appDir = Storage::build([
            'driver' => 'local',
            'root' => base_path('app'),
        ]);

        if ($packages->exists($installDirectory)) {
            $packages->deleteDirectory($installDirectory);
        }

        if ($appDir->exists('Http/Controllers/Admin')) {
            $appDir->deleteDirectory('Http/Controllers/Admin');
        }
        if ($appDir->exists('Http/Controllers/Api')) {
            $appDir->deleteDirectory('Http/Controllers/Api');
        }
        if ($appDir->exists('Http/Controllers/Site')) {
            $appDir->deleteDirectory('Http/Controllers/Site');
        }
        if ($appDir->exists('Resources')) {
            $appDir->deleteDirectory('Resources');
        }
        if ($appDir->exists('Helpers/LiveProduct.php')) {
            $appDir->delete('Helpers/LiveProduct.php');
        }
        if ($appDir->exists('Models/AdminUICore.php')) {
            $appDir->delete('Models/AdminUICore.php');
        }
        if ($appDir->exists('Http/Middleware/HandleInertiaRequests.php')) {
            $appDir->delete('Http/Middleware/HandleInertiaRequests.php');
        }

        $this->runCommand(["rm,", ".env"]);
        $this->runCommand(["cp", ".env-clean", ".env"]);
        $this->runCommand(["rm", "composer.json"]);
        $this->runCommand(["cp", "composer-clean.json", "composer.json"]);

        $this->runComposerUpdate();

        $this->cleanDatabase();

        return response()->json([
            'status'    => 'success',
            'message'   => 'Uninstall complete'
        ]);
    }

    public function cleanDatabase()
    {
        $tables = ["messages", "message_media", "activity_logs", "media_folders", "media", "meta_schemas", "navigations", "notifications", "redirects", "sent_emails", "seos", "setups", "states", "tax_rates"];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function runCommand(array $cmd)
    {
        $process = new Process($cmd);
        $process->setWorkingDirectory(base_path());
        $process->run();
    }
}
