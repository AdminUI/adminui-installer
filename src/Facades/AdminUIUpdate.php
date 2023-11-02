<?php

namespace AdminUI\AdminUIInstaller\Facades;

use Illuminate\Support\Facades\Facade;
use AdminUI\AdminUIInstaller\Services\UpdateService;

/**
 * @see \AdminUI\AdminUIInstaller\Services\UpdateService;
 */
class AdminUIUpdate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UpdateService::class;
    }
}
