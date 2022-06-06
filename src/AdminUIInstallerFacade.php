<?php

namespace AdminUI\AdminUIInstaller;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AdminUI\AdminUIInstaller\Skeleton\SkeletonClass
 */
class AdminUIInstallerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'adminui-installer';
    }
}
