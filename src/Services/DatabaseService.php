<?php

namespace AdminUI\AdminUIInstaller\Services;

use Illuminate\Support\Facades\DB;

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
}
