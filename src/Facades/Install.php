<?php

namespace AdminUI\AdminUIInstaller\Facades;

use AdminUI\AdminUIInstaller\Services\InstallService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getZipPath()
 * @method static string getExtractPath()
 * @method static \Illuminate\Filesystem\FilesystemAdapter getDisk()
 *
 * @see \AdminUI\AdminUIInstaller\Services\InstallService
 */
class Install extends Facade
{
    protected static function getFacadeAccessor()
    {
        return InstallService::class;
    }
}
