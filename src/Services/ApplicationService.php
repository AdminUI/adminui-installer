<?php

namespace AdminUI\AdminUIInstaller\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        // LOCAL FILE
        $filepath = base_path('composer.local.json');

        if (!file_exists($filepath)) {
            file_put_contents($filepath, "{}");
        }

        $jsonRaw = file_get_contents($filepath) ?? "{}";
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
            $json['require']['adminui/adminui'] = '*';
        }

        $newJsonRaw = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('composer.local.json'), $newJsonRaw);
        unset($jsonRaw);
        unset($json);

        // MAIN FILE
        $original = base_path('composer.json');
        $jsonRaw = file_get_contents($original) ?? "{}";
        $json = json_decode($jsonRaw, true);
        if (!isset($json['extra'])) {
            $json['extra'] = (object) [];
        }
        if (!isset($json['extra']["merge-plugin"])) {
            $json['extra']["merge-plugin"] = (object) [
                'include' => ['composer.local.json']
            ];
        }
        $newJsonRaw = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('composer.json'), $newJsonRaw);
    }

    public function composerUpdate($callback = null)
    {
        app()->make(\AdminUI\AdminUIInstaller\Helpers\Composer::class)->run(['update', "--no-progress", "--no-audit", "--no-scripts", "--no-interaction"], $callback);
    }

    public function checkForComposerUpdate($packageLocation, $outputCallback)
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
        $uuid = Str::uuid();
        Artisan::call('down', [
            '--render' => 'adminui-installer::maintenance',
            '--secret' => $uuid
        ]);
        return $uuid;
    }

    protected function hashLockFileContents(string $root)
    {
        $path = $root . "/composer.json";
        return hash_file('sha256', $path);
    }
}
