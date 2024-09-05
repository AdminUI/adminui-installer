<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\DB;

class TruncateDatabaseAction
{
    public function execute()
    {
        $defaultId = 1000;
        $tables = [
            'accounts',
            'users',
            'account_user',
            'addresses',
            'activity_logs',
            'backorders',
            'dispatch_notes',
            'emails',
            'failed_jobs',
            'form_failures',
            'form_submissions',
            'jobs',
            'messages',
            'notifications',
            'orders',
            'order_items',
            'order_histories',
            'order_integrations',
            'order_item_histories',
            'password_resets',
            'subscribers',
            'webshook_calls',
        ];

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            // truncate each table
            DB::table($table)->truncate();
            // start the auto increment at defaultId
            DB::statement("ALTER TABLE $table AUTO_INCREMENT = $defaultId;");
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
