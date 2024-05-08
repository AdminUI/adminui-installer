<?php

namespace AdminUI\AdminUIInstaller\Actions;

class UpdateComposerFileAction
{
    public function execute()
    {
        $original = base_path('composer.json');
        $jsonRaw = file_get_contents($original) ?? '{}';
        $json = json_decode($jsonRaw, true);
        if (! isset($json['extra'])) {
            $json['extra'] = (object) [];
        }
        if (! isset($json['extra']['merge-plugin'])) {
            $json['extra']['merge-plugin'] = (object) [
                'include' => ['composer.local.json'],
            ];
        }
        $newJsonRaw = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('composer.json'), $newJsonRaw);
    }
}
