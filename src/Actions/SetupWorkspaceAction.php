<?php

namespace AdminUI\AdminUIInstaller\Actions;

/**
 * This action sets up workspace-related files
 */
class SetupWorkspaceAction
{
    public function execute()
    {
        $path = base_path('.vscode/extensions.json');

        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $jsonRaw = file_exists($path)
            ? file_get_contents($path)
            : '{}';
        $json = json_decode($jsonRaw, true);

        if (! isset($json['recommendations']) || ! is_array($json['recommendations'])) {
            $json['recommendations'] = [];
        }

        $recommended = [
            "shufo.vscode-blade-formatter",
            "onecentlin.laravel-blade",
            "mehedidracula.php-namespace-resolver",
            "esbenp.prettier-vscode",
            "dbaeumer.vscode-eslint",
            "vue.volar"
        ];

        $json['recommendations'] = array_values(array_unique([
            ...$json['recommendations'],
            ...$recommended,
        ]));

        $newJsonRaw = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($path, $newJsonRaw);
    }
}
