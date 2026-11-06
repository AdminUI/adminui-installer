<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use AdminUI\AdminUIInstaller\Actions\CheckRegistrationStatusAction;
use AdminUI\AdminUIInstaller\Actions\CreateFirstAdminAction;
use AdminUI\AdminUIInstaller\Facades\Json;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function index(CheckRegistrationStatusAction $status)
    {
        // The SPA renders every screen, the server only decides which one it opens on
        $page = match ($status->execute()) {
            CheckRegistrationStatusAction::NOT_INSTALLED => 'not-installed',
            CheckRegistrationStatusAction::ALREADY_REGISTERED => 'already-registered',
            default => 'register',
        };

        return view('adminui-installer::index', [
            'page' => $page,
            'status' => Json::get(),
        ]);
    }

    public function store(CheckRegistrationStatusAction $status, CreateFirstAdminAction $action, Request $request)
    {
        // Refuse before validating, so a blocked request never reaches the password breach check
        if ($blocked = $this->blockedResponse($status->execute())) {
            return $blocked;
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'company' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)->uncompromised(3)->letters()->mixedCase()->numbers()],
        ]);

        try {
            // Check again under a lock so two simultaneous requests can't both pass the check and create an admin
            return Cache::lock('adminui-installer:register', 30)->block(5, function () use ($status, $action, $validated) {
                if ($blocked = $this->blockedResponse($status->execute())) {
                    return $blocked;
                }

                $action->execute($validated);

                return response()->json();
            });
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'Registration is already in progress.'], 409);
        }
    }

    private function blockedResponse(string $status): ?JsonResponse
    {
        return match ($status) {
            CheckRegistrationStatusAction::NOT_INSTALLED => response()->json([
                'message' => 'AdminUI must be installed before an admin account can be registered.',
            ], 403),
            CheckRegistrationStatusAction::ALREADY_REGISTERED => response()->json([
                'message' => 'An admin account has already been registered.',
            ], 403),
            default => null,
        };
    }
}
