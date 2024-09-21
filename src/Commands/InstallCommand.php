<?php

namespace AdminUI\AdminUIInstaller\Commands;

use AdminUI\AdminUIInstaller\Actions\WriteLaravelElevenChangesAction;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'adminui:install';

    protected $description = 'Install AdminUI in a new Laravel Installation';

    public function handle(WriteLaravelElevenChangesAction $action)
    {
        $result = $action->execute();

        $this->info("Done");
    }
}
