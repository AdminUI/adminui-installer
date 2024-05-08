<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\DB;

class CheckForUpdateAction
{
    public function execute()
    {
        try {
            DB::select('SHOW TABLES');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
