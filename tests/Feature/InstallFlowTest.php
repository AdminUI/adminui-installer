<?php

use AdminUI\AdminUIInstaller\Actions\CheckDatabaseConnectionAction;
use AdminUI\AdminUIInstaller\Actions\CleanupInstallAction;
use AdminUI\AdminUIInstaller\Actions\CreateLocalComposerFileAction;
use AdminUI\AdminUIInstaller\Actions\DownloadLatestReleaseAction;
use AdminUI\AdminUIInstaller\Actions\EnableCacheSerializationAction;
use AdminUI\AdminUIInstaller\Actions\GetLatestReleaseDetailsAction;
use AdminUI\AdminUIInstaller\Actions\UnpackReleaseAction;
use AdminUI\AdminUIInstaller\Actions\UpdateComposerFileAction;
use AdminUI\AdminUIInstaller\Actions\ValidateDownloadAction;
use AdminUI\AdminUIInstaller\Facades\Install;
use AdminUI\AdminUIInstaller\Facades\Json;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/*
 * Runs the real install steps against the fake release server (dev/fake-release-server),
 * using a throwaway sqlite file in tests/database.
 *
 * Needs a build first:  php dev/fake-release-server/build.php --source=/path/to/adminui
 * Stops after unpack + composer file prep: composer/publish/migrate/seed need a full Laravel app with
 * AdminUI's dependencies installed, which Testbench doesn't have.
 */

const FAKE_SERVER_PORT = 8097; // not 8099, which dev/e2e uses

beforeAll(function () {
    $GLOBALS['fakeServer'] = null;

    $router = dirname(__DIR__, 2).'/dev/fake-release-server/router.php';
    if (! is_file(dirname($router).'/storage/adminui.zip')) {
        return;
    }

    $process = new Process([PHP_BINARY, '-S', '127.0.0.1:'.FAKE_SERVER_PORT, $router], null, ['FAKE_RELEASE_KEY' => 'test-licence']);
    $process->start();

    for ($i = 0; $i < 50; $i++) {
        if (@fsockopen('127.0.0.1', FAKE_SERVER_PORT)) {
            $GLOBALS['fakeServer'] = $process;

            return;
        }
        usleep(100_000);
    }
    $process->stop();
});

afterAll(function () {
    $GLOBALS['fakeServer']?->stop();
});

beforeEach(function () {
    if (! $GLOBALS['fakeServer']) {
        $this->markTestSkipped('Fake release server unavailable - run dev/fake-release-server/build.php first.');
    }

    $db = __DIR__.'/../database/database.sqlite';
    @unlink($db);
    touch($db);
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => realpath($db),
        'adminui-installer.version_endpoint' => 'http://127.0.0.1:'.FAKE_SERVER_PORT.'/api/update',
        'adminui-installer.licence' => 'test-licence',
    ]);

    cleanSandbox();
});

afterEach(fn () => cleanSandbox());

function cleanSandbox(): void
{
    File::deleteDirectory(base_path('packages'));
    foreach (['composer.local.json'] as $file) {
        @unlink(base_path($file));
    }
    if ($GLOBALS['fakeServer'] ?? false) {
        app(CleanupInstallAction::class)->execute();
    }
}

it('connects to the sqlite test database', function () {
    expect(app(CheckDatabaseConnectionAction::class)->execute())->toBeTrue();
});

it('rejects an invalid licence key', function () {
    config(['adminui-installer.licence' => 'wrong-key']);

    app(GetLatestReleaseDetailsAction::class)->execute();
})->throws(Exception::class, 'invalid');

it('downloads, validates and unpacks the release', function () {
    $release = app(GetLatestReleaseDetailsAction::class)->execute();
    expect($release)->toHaveKeys(['version', 'url', 'shasum', 'changelog']);

    app(DownloadLatestReleaseAction::class)->execute();
    expect(Install::getDisk()->exists(Install::getZipPath()))->toBeTrue();
    expect(app(ValidateDownloadAction::class)->execute($release['shasum']))->toBeTrue();

    app(UnpackReleaseAction::class)->execute();

    expect(base_path('packages/adminui/composer.json'))->toBeFile();
    $composer = json_decode(file_get_contents(base_path('packages/adminui/composer.json')), true);
    expect($composer['name'])->toBe('adminui/adminui');
    expect(Json::getField('installSize'))->not->toBeEmpty();
});

it('fails validation on a wrong checksum', function () {
    app(GetLatestReleaseDetailsAction::class)->execute();
    app(DownloadLatestReleaseAction::class)->execute();

    app(ValidateDownloadAction::class)->execute('not-the-checksum');
})->throws(Exception::class, 'authenticity');

it('prepares composer files for the unpacked package', function () {
    app(CreateLocalComposerFileAction::class)->execute();

    $local = json_decode(file_get_contents(base_path('composer.local.json')), true);
    expect($local['repositories'][0]['url'])->toBe('./packages/adminui')
        ->and($local['require'])->toHaveKey('adminui/adminui');

    $original = file_get_contents(base_path('composer.json'));
    try {
        app(UpdateComposerFileAction::class)->execute();
        $root = json_decode(file_get_contents(base_path('composer.json')), true);
        expect($root['extra']['merge-plugin']['include'])->toContain('composer.local.json')
            ->and($root['config']['allow-plugins']['wikimedia/composer-merge-plugin'])->toBeTrue();
    } finally {
        file_put_contents(base_path('composer.json'), $original);
    }
});

it('enables cache.serializable_classes in the application config', function () {
    $path = base_path('config/cache.php');
    $original = file_get_contents($path);

    try {
        // Laravel 13 default
        file_put_contents($path, "<?php\n\nreturn [\n    'default' => 'database',\n    'serializable_classes' => false,\n];\n");
        expect(app(EnableCacheSerializationAction::class)->execute())->toBeTrue();
        expect(file_get_contents($path))->toContain("'serializable_classes' => true");

        // Already enabled: leave it alone
        expect(app(EnableCacheSerializationAction::class)->execute())->toBeFalse();

        // Laravel 12 and earlier have no such option: nothing to change
        file_put_contents($path, "<?php\n\nreturn [\n    'default' => 'file',\n];\n");
        expect(app(EnableCacheSerializationAction::class)->execute())->toBeFalse();
        expect(file_get_contents($path))->not->toContain('serializable_classes');
    } finally {
        file_put_contents($path, $original);
    }
});
