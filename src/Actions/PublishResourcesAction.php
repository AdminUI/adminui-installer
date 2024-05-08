<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PublishResourcesAction
{
    public function execute()
    {
        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-setup-only',
            '--force' => true,
        ]);
        $output = Artisan::output();

        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-addons-public',
            '--force' => true,
        ]);
        $output .= Artisan::output();

        Artisan::call('vendor:publish', [
            '--tag' => ['spatie-permission-config', 'spatie-permission-migrations'],
            '--force' => true,
        ]);
        $output .= Artisan::output();

        if (! Schema::hasTable('jobs')) {
            Artisan::call('queue:table');
        }

        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-public',
            '--force' => true,
        ]);
        $output .= Artisan::output();

        Artisan::call('config:clear');
        $output .= Artisan::output();

        return Str::of($output)->explode("\n")->filter(fn ($item) => ! empty(trim($item)))->values();
    }
}
