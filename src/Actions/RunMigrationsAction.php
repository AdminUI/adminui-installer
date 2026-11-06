<?php

namespace AdminUI\AdminUIInstaller\Actions;

use AdminUI\AdminUIInstaller\Services\CallbackOutput;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class RunMigrationsAction
{
    /**
     * WARNING: If `update` is not `true`, the database will be wiped
     */
    public function execute(bool $update = false, ?callable $onOutput = null)
    {
        $cmd = $update === true ? 'migrate' : 'migrate:fresh';
        $out = new CallbackOutput($onOutput);
        Artisan::call($cmd.' --no-interaction --force', [], $out);
        $output = $out->fetch();

        return Str::of($output)
            ->explode("\n")
            ->filter(fn ($item) => ! empty(trim($item)))
            ->values();
    }
}
