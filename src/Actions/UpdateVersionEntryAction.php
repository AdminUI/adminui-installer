<?php

namespace AdminUI\AdminUIInstaller\Actions;

class UpdateVersionEntryAction
{
    public function execute(string $version = 'v0.0.1')
    {
        return \AdminUI\AdminUI\Models\Configuration::updateOrCreate(
            ['name' => 'installed_version'],
            ['section' => 'private', 'type' => 'text', 'label' => 'Installed Version', 'value' => $version],
        );
    }
}
