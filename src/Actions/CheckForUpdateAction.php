<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\DB;

class CheckForUpdateAction
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
