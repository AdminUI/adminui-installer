<?php

namespace AdminUI\AdminUIInstaller\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Facades\AdminUIUpdate;
use AdminUI\AdminUIInstaller\Facades\AdminUIInstaller;
use Facades\AdminUI\AdminUIInstaller\Controllers\UninstallController;

class CheckForUpdateCommand extends Command
{
    protected $signature = 'adminui:update';

    protected $description = 'Check for AdminUI update';

    public function handle()
    {
        try {
            $details = AdminUIUpdate::check();
            if (isset($details['update']['url'])) {
                $this->info("Downloading AdminUI " . $details['update']['version']);
                $url = $details['update']['url'];

                // Download
                AdminUIInstaller::downloadPackage(config('adminui.licence_key'), $url, AdminUIUpdate::getZipPath());
                AdminUIUpdate::update(fn ($line) => $this->info($line), $details['update']['version'], false);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
