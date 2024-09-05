<?php

namespace App\Actions;

class CreateDefaultAccountAction
{
    public function execute()
    {
        // run the account db seeder
        $seeder = new \AdminUI\AdminUI\Database\Seeds\AccountSeeder();
        $seeder->run();
    }
}
