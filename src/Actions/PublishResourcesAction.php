<?php

namespace AdminUI\AdminUIInstaller\Actions;

use AdminUI\AdminUIInstaller\Services\CallbackOutput;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PublishResourcesAction
{
    public function execute(?callable $onOutput = null)
    {
        $out = new CallbackOutput($onOutput);

        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-setup-only',
            '--force' => true,
        ], $out);

        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-addons-public',
            '--force' => true,
        ], $out);

        Artisan::call('vendor:publish', [
            '--tag' => ['spatie-permission-config', 'spatie-permission-migrations'],
            '--force' => true,
        ], $out);

        if (! Schema::hasTable('jobs')) {
            Artisan::call('queue:table');
        }

        Artisan::call('vendor:publish', [
            '--tag' => 'adminui-public',
            '--force' => true,
        ], $out);

        try {
            app(WriteLaravelElevenChangesAction::class)->execute();
        } catch (\Exception $e) {
            $out->writeln('Unable to write bootstrap/app file: '.$e->getMessage());
        }

        Artisan::call('config:clear', [], $out);

        return Str::of($out->fetch())->explode("\n")->filter(fn ($item) => ! empty(trim($item)))->values();
    }
}
