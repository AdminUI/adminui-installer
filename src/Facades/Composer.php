<?php

namespace AdminUI\AdminUIInstaller\Facades;

use AdminUI\AdminUIInstaller\Services\ComposerService;
use Illuminate\Support\Facades\Facade;

class Composer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ComposerService::class;
    }
}
