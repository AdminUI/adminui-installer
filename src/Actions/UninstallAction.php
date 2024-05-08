<?php

namespace AdminUI\AdminUIInstaller\Actions;

use AdminUI\AdminUIInstaller\Facades\Json;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UninstallAction
{
    public function execute()
    {
        /* *********************************************
         * Wipe DB
         * ******************************************* */
        $tables = collect(DB::select('SHOW TABLES'));
        if ($tables->count() > 0) {
            Artisan::call('db:wipe', [
                '--force' => true,
                '--no-interaction' => true,
            ]);
        }

        /* *********************************************
         * Wipe published
         * ******************************************* */
        $permissionMigration = base_path('database/migrations/2000_01_01_create_permission_tables.php');
        if (file_exists($permissionMigration)) {
            unlink($permissionMigration);
        }

        $permissionConfig = config_path('permission.php');
        if (file_exists($permissionConfig)) {
            unlink($permissionConfig);
        }

        /* *********************************************
         * Delete composer file
         * ******************************************* */
        $localComposerJson = base_path('composer.local.json');
        if (file_exists($localComposerJson)) {
            unset($localComposerJson);
        }

        /* *********************************************
         * Clear cache
         * ******************************************* */
        Artisan::call('optimize:clear');

        /* *********************************************
         * Update composer
         * ******************************************* */
        $updateComposerAction = app(ComposerUpdateAction::class);
        $updateComposerAction->execute();

        /* *********************************************
         * Erase status.json file
         * ******************************************* */
        $blankJson = (object) [];
        Json::set($blankJson);

        /* *********************************************
         * Remove AdminUI package
         * ******************************************* */
        /** @var Filesystem */
        $packages = Storage::build([
            'driver' => 'local',
            'root' => base_path('packages'),
        ]);
        $installDirectory = 'adminui';
        $packages->deleteDirectory($installDirectory);
    }
}
