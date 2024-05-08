<?php

namespace AdminUI\AdminUIInstaller\Actions;

class SeedDatabaseAction
{
    public function execute()
    {
        $dbSeeder = new \AdminUI\AdminUI\Database\Seeds\DatabaseSeeder;
        $dbSeeder->run();
    }
}
