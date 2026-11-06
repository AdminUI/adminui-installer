<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\DB;

class CheckDatabaseConnectionAction
{
    public function execute()
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
