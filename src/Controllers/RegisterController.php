<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

class RegisterController extends BaseInstallController
{

    public function index()
    {
        $isInstalled = $this->checkIfInstalled();

        if ($isInstalled === false) {
            return view('adminui-installer::not-installed');
        }

        $isRegistered = \AdminUI\AdminUI\Models\Admin::all()->count() > 0 ?? false;

        if ($isRegistered) return view('adminui-installer::already-registered');
        else return view('adminui-installer::register')->with('prefix', config('adminui.prefix'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'    => ['required', 'string'],
            'last_name'     => ['required', 'string'],
            'email'         => ['required', 'email'],
            'company'       => ['required', 'string'],
            'password'      => ['required', 'confirmed', Password::min(6)->uncompromised(3)->letters()->mixedCase()->numbers()]
        ]);

        $admin              = new \AdminUI\AdminUI\Models\Admin();
        $admin->id          = 1000;
        $admin->first_name  = $validated['first_name'];
        $admin->last_name   = $validated['last_name'];
        $admin->email       = $validated['email'];
        $admin->password    = $validated['password'];
        $admin->username    = strtolower($validated['first_name'] . '.' . $validated['last_name']);
        $admin->save();

        $contact            = new \AdminUI\AdminUI\Models\AdminContact();
        $contact->admin_id  = $admin->id;
        $contact->save();

        $contract           = new \AdminUI\AdminUI\Models\AdminContract();
        $contract->admin_id = $admin->id;
        $contract->save();

        $config             = \AdminUI\AdminUI\Models\Configuration::where('name', 'company')->first();
        $config->value      = $validated['company'];
        $config->save();

        // Add any other permissions
        $permissions = \Spatie\Permission\Models\Permission::where('guard_name', 'admin')->get();

        foreach ($permissions as $permission) {
            $admin->givePermissionTo($permission);
        }

        return $this->sendSuccess();
    }
}
