<?php

namespace AdminUI\AdminUIInstaller\Commands;

use AdminUI\AdminUIInstaller\Actions\UninstallAction;
use Illuminate\Console\Command;

class UninstallCommand extends Command
{
    protected $signature = 'adminui:uninstall';

    protected $description = 'Remove AdminUI from a Laravel application';

    public function handle(UninstallAction $action)
    {
        $action->execute();

        $this->info('Uninstall complete');
    }
}
