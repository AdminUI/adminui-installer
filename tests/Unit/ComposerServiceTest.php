<?php

use AdminUI\AdminUIInstaller\Services\ComposerService;

/**
 * Points the service at a stub "composer.phar" so we can control its exit code.
 */
function stubComposer(string $php): ComposerService
{
    $root = sys_get_temp_dir().'/adminui-composer-stub-'.uniqid();
    mkdir($root.'/lib', 0775, true);
    file_put_contents($root.'/lib/composer.phar', "<?php\n".$php);
    config(['adminui-installer.root' => $root]);

    return new ComposerService;
}

it('returns the output of a successful composer run', function () {
    $output = stubComposer('echo "all good"; exit(0);')->run('update');

    expect($output->implode(''))->toContain('all good');
});

it('throws with composer\'s output when composer fails', function () {
    stubComposer('echo "Your requirements could not be resolved"; exit(2);')->run('update');
})->throws(RuntimeException::class, 'Your requirements could not be resolved');
