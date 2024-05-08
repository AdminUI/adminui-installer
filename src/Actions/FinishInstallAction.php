<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class FinishInstallAction
{
    public function execute(string $version = 'v0.0.1')
    {
        $updateVersionAction = app(UpdateVersionEntryAction::class);
        $updateVersionAction->execute($version);

        if (Storage::exists('media') === false) {
            Storage::makeDirectory('media');
        }

        Artisan::call('storage:link');
    }
}
