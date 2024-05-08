<?php

namespace AdminUI\AdminUIInstaller\Actions;

class CreateLocalComposerFileAction
{
    public function execute()
    {
        // LOCAL FILE
        $filepath = base_path('composer.local.json');

        if (! file_exists($filepath)) {
            file_put_contents($filepath, '{}');
        }

        $jsonRaw = file_get_contents($filepath) ?? '{}';
        $json = json_decode($jsonRaw, true);

        if (! isset($json['repositories'])) {
            $json['repositories'] = [];
        }

        if (array_search('./packages/adminui', array_column($json['repositories'], 'url')) === false) {
            $json['repositories'][] = [
                'type' => 'path',
                'url' => './packages/adminui',
                'options' => [
                    'symlink' => true,
                ],
            ];
        }

        if (! isset($json['require'])) {
            $json['require'] = [];
        }

        if (! isset($json['require']['adminui/adminui'])) {
            $json['require']['adminui/adminui'] = '*';
        }

        $newJsonRaw = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('composer.local.json'), $newJsonRaw);
    }
}
