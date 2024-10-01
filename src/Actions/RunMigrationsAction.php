<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class RunMigrationsAction
{
    public function execute(bool $update = false)
    {
        $cmd = $update === true ? 'migrate' : 'migrate:fresh';
        Artisan::call($cmd . " --no-interaction");
        $output = Artisan::output();

        return Str::of($output)
            ->explode("\n")
            ->filter(fn($item) => ! empty(trim($item)))
            ->values();
    }
}
