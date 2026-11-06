<?php

namespace AdminUI\AdminUIInstaller\Tests;

use AdminUI\AdminUIInstaller\Provider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            Provider::class,
        ];
    }
}
