<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use AdminUI\AdminUIInstaller\Services\InstallerService;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

class RegisterController extends BaseInstallController
{

    public function __construct(
        protected InstallerService $installerService,
    ) {
    }

    public function index()
    {
        $isInstalled = $this->installerService->checkIfInstalled();

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

        DB::transaction(function () use ($validated) {
            $admin = \AdminUI\AdminUI\Models\Admin::create($validated);

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
        });

        return $this->sendFailed("Error creating new admin");
    }
}
