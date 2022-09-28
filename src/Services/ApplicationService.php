<?php

namespace AdminUI\AdminUIInstaller\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\PhpExecutableFinder;

class ApplicationService
{
    /**
     * updateEnvironmentVariables - Adds required entries to the root .env files
     *
     * @param  string $licenceKey
     * @return void
     */
    public function updateEnvironmentVariables(string $licenceKey)
    {
        $inserts = [
            'ADMINUI_PREFIX'            => 'admin',
            'ADMINUI_LICENCE_KEY'       => $licenceKey,
        ];

        foreach ($inserts as $key => $value) {
            $this->setEnvironmentValue($key, $value);
        }

        Artisan::call("cache:clear");
        Artisan::call("config:clear");
    }

    public function setEnvironmentValue($key, $value)
    {
        $path = app()->environmentFilePath();

        if (file_exists($path)) {
            if (getenv($key)) {
                //replace variable if key exit
                file_put_contents($path, str_replace(
                    "$key=" . getenv($key),
                    "$key=" . '"' . $value . '"',
                    file_get_contents($path)
                ));
            } else {
                //set if variable key not exit
                $file   = file($path);
                $file[] = PHP_EOL . "$key=" . '"' . $value . '"';
                file_put_contents($path, $file);
            }
        }
    }

    /**
     * updateComposerJson - Adds required entries into the root composer.json file
     *
     * @return void
     */
    public function updateComposerJson()
    {
        $jsonRaw = file_get_contents(base_path('composer.json'));
        $json = json_decode($jsonRaw, true);

        if (!isset($json['repositories'])) {
            $json['repositories'] = [];
        }

        if (array_search('./packages/adminui', array_column($json['repositories'], 'url')) === false) {
            $json['repositories'][] = [
                "type" => "path",
                "url" => "./packages/adminui",
                "options" => [
                    "symlink" => true
                ]
            ];
        }

        if (!isset($json['require'])) {
            $json['require'] = [];
        }

        if (!isset($json['require']['adminui/adminui'])) {
            $json['require']['adminui/adminui'] = '@dev';
        }

        $newJsonRaw = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('composer.json'), $newJsonRaw);
    }

    public function composerUpdate()
    {
        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();
        $composerPath = config()->get('adminui-installer.base_path') . '/lib/composer.phar';

        if (file_exists($composerPath) === false) {
            throw new \Exception("Unable to find composer.phar. Looking for " . $composerPath);
        }

        $process = new Process([$phpBinaryPath, config('adminui-installer.base_path') . '/lib/composer.phar', "update", "--no-interaction", "--no-scripts"], null, ["PATH" => '$PATH:/usr/local/bin']);
        $process->setTimeout(300);
        $process->setWorkingDirectory(base_path());
        $process->run();

        if ($process->isSuccessful()) {
            return $process->getOutput();
        } else {
            throw new \Exception("Composer error:" . $process->getErrorOutput());
        }
    }

    public function checkForComposerUpdate($packageLocation)
    {
        $updateHash = $this->hashLockFileContents($packageLocation);
        $installedHash = \AdminUI\AdminUI\Models\Configuration::where('name', 'installed_composer_hash')->firstOrCreate(
            ['name'  => 'installed_composer_hash'],
            [
                'label' => 'Composer JSON file hash',
                'value' => '',
                'section' => 'private',
                'type'  => 'text'
            ]
        );

        if ($updateHash !== $installedHash) {
            $this->composerUpdate();
            $installedHash->value = $updateHash;
            $installedHash->save();
        }
    }

    public function flushCache()
    {
        Artisan::call('optimize:clear');
        $result = Artisan::output();

        Artisan::call('optimize');
        return $result;
    }

    /**
     * cleanUpdateDirectory - Makes sure the temporary install directory is empty
     */
    public function cleanUpdateDirectory(string $zipPath, $extractPath): bool
    {
        if (Storage::exists($zipPath)) {
            Storage::delete($zipPath);
        }
        if (Storage::exists($extractPath)) {
            Storage::deleteDirectory($extractPath);
        }
        return true;
    }

    public function down()
    {
        Artisan::call('down', [
            '--render' => 'adminui-installer::maintenance'
        ]);
    }

    protected function hashLockFileContents(string $root)
    {
        $path = $root . "/composer.json";
        return hash_file('sha256', $path);
    }
}
