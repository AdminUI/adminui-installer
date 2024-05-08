<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MaintenanceModeEnterAction
{
    public function execute(): string
    {
        $uuid = Str::uuid();
        Artisan::call('down', [
            '--render' => 'adminui-installer::maintenance',
            '--secret' => $uuid,
        ]);

        return $uuid;
    }
}
