<?php

use AdminUI\AdminUIInstaller\Provider;

it('registers the installer config', function () {
    expect(config('adminui-installer'))->toBeArray()
        ->and(config('adminui-installer.root'))->toBe(dirname((new ReflectionClass(Provider::class))->getFileName(), 2));
});

it('registers the installer commands', function () {
    expect(array_keys(Artisan::all()))->toContain(
        'adminui:install',
        'adminui:refresh',
        'adminui:uninstall',
        'adminui:update',
        'adminui:golive',
    );
});
