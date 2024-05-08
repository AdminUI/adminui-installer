<?php

namespace AdminUI\AdminUIInstaller\Actions;

use AdminUI\AdminUIInstaller\Facades\Composer;
use AdminUI\AdminUIInstaller\Facades\Json;
use Illuminate\Support\Str;

class ComposerUpdateAction
{
    public function execute()
    {
        $output = Composer::run('update --no-interaction --no-ansi');
        Json::setField(field: 'composerLog', data: Str::replace("\n", '', $output));

        return $output;
    }
}
