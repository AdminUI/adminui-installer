<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use AdminUI\AdminUI\Traits\ApiResponseTrait;
use AdminUI\AdminUIInstaller\Actions\UpdatePackageFromUrlAction;

class PackageController extends Controller
{
    use ApiResponseTrait;

    public function __invoke(Request $request, UpdatePackageFromUrlAction $action)
    {
        $validated = $request->validate([
            'name' => ['required'],
            'version' => ['required'],
            'shasum' => ['required'],
            'url' => ['required'],
        ]);

        $action->execute($validated['name'], $validated['url'], $validated['shasum'], $validated['version']);

        return $this->respondSuccess($validated);
    }
}
