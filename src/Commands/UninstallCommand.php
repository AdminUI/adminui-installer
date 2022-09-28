<?php

namespace AdminUI\AdminUIInstaller\Commands;

use Facades\AdminUI\AdminUIInstaller\Controllers\UninstallController;
use Illuminate\Console\Command;

class UninstallCommand extends Command
{
    protected $signature = 'adminui:uninstall';

    protected $description = 'Remove AdminUI from app';

    public function handle()
    {
        UninstallController::index();
    }
}
