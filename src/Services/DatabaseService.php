<?php

namespace AdminUI\AdminUIInstaller\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class DatabaseService
{
    /**
     * Verifies if a valid database connection is available to the installer
     */
    public function check(): bool
    {
        try {
            DB::select('SHOW TABLES');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function spatieMigrations(): bool
    {
        $baseDir = Storage::build([
            'driver' => 'local',
            'root' => base_path('')
        ]);


        if (Schema::hasTable('permissions') === false) {
            $path = base_path("database/migrations");
            $files = File::allFiles($path);
            $found = array_filter($files, function ($v, $k) {
                return preg_match("/create_permission_tables.php$/", $v);
            }, ARRAY_FILTER_USE_BOTH);
            $foundFlat = array_merge($found);
            $migrationPath = "database/migrations/" . $foundFlat[0]->getFilename();
            $updatedMigrationPath = preg_replace('/\d{4}_\d{2}_\d{2}/', '2000_01_01', $migrationPath);
            $baseDir->move($migrationPath, $updatedMigrationPath);
            sleep(1);
            Artisan::call("migrate --path=\"" . $updatedMigrationPath . "\"");
        }
        return true;
    }

    /**
     * migrateAndSeedUpdate - Runs the required migration and seed paths for updating AdminUI
     */
    public function migrateAndSeedUpdate(): array
    {
        $output = [];
        // Migrate any db updates
        Artisan::call('migrate', [
            '--force' => true
        ]);
        $output[] = Artisan::output();

        // Update database seeds
        // Update adminui navigation seeds
        $output[] = "Running AdminUI seeders";
        Artisan::call('db:seed', [
            '--class' => 'AdminUI\AdminUI\Database\Seeds\DatabaseSeederUpdate',
            '--force' => true
        ]);
        $output[] = Artisan::output();

        //  Frontend site specific seeds
        if (file_exists(base_path('database/seeders/AdminUIUpdateSeeder.php'))) {
            $output[] = "Running DB update seed";
            Artisan::call('db:seed', [
                '--class' => 'Database\Seeders\AdminUIUpdateSeeder',
                '--force' => true
            ]);
            $output[] = Artisan::output();
        }
        return $output;
    }
}
