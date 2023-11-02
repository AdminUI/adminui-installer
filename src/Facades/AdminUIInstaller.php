<?php

namespace AdminUI\AdminUIInstaller\Facades;

use AdminUI\AdminUIInstaller\Services\InstallerService;
use Illuminate\Support\Facades\Facade;

/**
 * @see AdminUI\AdminUI\Services\NavService;
 */
class AdminUIInstaller extends Facade
{
    protected static function getFacadeAccessor()
    {
        return InstallerService::class;
    }
}
