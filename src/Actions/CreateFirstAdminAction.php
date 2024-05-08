<?php

namespace AdminUI\AdminUIInstaller\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateFirstAdminAction
{
    public function execute(array $data)
    {
        DB::transaction(function () use ($data) {
            $adminModel = app(\AdminUI\AdminUI\Models\Admin::class);
            $admin = $adminModel::create($data);

            $contact = new \AdminUI\AdminUI\Models\AdminContact();
            $contact->admin_id = $admin->id;
            $contact->save();

            $contract = new \AdminUI\AdminUI\Models\AdminContract();
            $contract->admin_id = $admin->id;
            $contract->save();

            $config = \AdminUI\AdminUI\Models\Configuration::where('name', 'company')->first();
            $config->value = $data['company'];
            $config->save();

            $admin->assignRole('Super Admin');
            $admin->syncPermissions([]);

            Auth::guard('admin')->login($admin);
        });
    }
}
