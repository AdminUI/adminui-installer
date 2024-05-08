<?php

namespace AdminUI\AdminUIInstaller\Actions;

class SeedDatabaseUpdateAction
{
    public function execute()
    {
        $dbSeeder = new \AdminUI\AdminUI\Database\Seeds\DatabaseSeederUpdate;
        $dbSeeder->run();
    }
}
