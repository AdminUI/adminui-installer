<?php

namespace AdminUI\AdminUIInstaller\Actions;

/**
 * This actions makes necessary changes to the the root `composer.json` file to enable key features
 */
class UpdateComposerFileAction
{
    public function execute()
    {
        $original = base_path('composer.json');
        $jsonRaw = file_get_contents($original) ?? '{}';
        $json = json_decode($jsonRaw, true);

        // Extras
        if (! isset($json['extra'])) {
            $json['extra'] = (object) [];
        }
        if (! isset($json['extra']['merge-plugin'])) {
            $json['extra']['merge-plugin'] = (object) [
                'include' => ['composer.local.json'],
            ];
        }

        // Config
        if (! isset($json['config'])) {
            $json['config'] = (object) [];
        }
        if (! isset($json['config']['allow-plugins'])) {
            $json['config']['allow-plugins'] = (object) [];
        }

        $json['config']['allow-plugins']['pixelfear/composer-dist-plugin'] = true;
        $json['config']['allow-plugins']['wikimedia/composer-merge-plugin'] = true;

        $newJsonRaw = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('composer.json'), $newJsonRaw);
    }
}
