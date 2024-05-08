<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use AdminUI\AdminUIInstaller\Actions\CreateFirstAdminAction;
use AdminUI\AdminUIInstaller\Facades\Install;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function index()
    {
        $isInstalled = Install::isInstalled();

        if ($isInstalled === false) {
            return view('adminui-installer::not-installed');
        }

        $adminModel = app(\AdminUI\AdminUI\Models\Admin::class);
        $isRegistered = $adminModel::all()->count() > 0 ?? false;

        if ($isRegistered) {
            return view('adminui-installer::already-registered');
        } else {
            return view('adminui-installer::register')->with('prefix', config('adminui.prefix'));
        }
    }

    public function store(CreateFirstAdminAction $action, Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'company' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)->uncompromised(3)->letters()->mixedCase()->numbers()],
        ]);

        $action->execute($validated);

        return response()->json();
    }
}
