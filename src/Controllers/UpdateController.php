<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Parsedown;
use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Services\DatabaseService;
use AdminUI\AdminUIInstaller\Services\InstallerService;
use AdminUI\AdminUIInstaller\Services\ApplicationService;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;
use AdminUI\AdminUIInstaller\Facades\AdminUIUpdate;

class UpdateController extends BaseInstallController
{
    public function __construct(
        protected ApplicationService $appService,
        protected DatabaseService $dbService,
        protected InstallerService $installerService
    ) {
    }

    /**
     * checkUpdate - Route controller for checking update availability
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function checkUpdate(Request $request)
    {
        try {
            $details = AdminUIUpdate::check();
            return $this->sendSuccess($details);
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }
    }

    /**
     * updateSystem - Route controller for installing an update
     *
     * @param  mixed $request
     * @return void
     */
    public function updateSystem(Request $request)
    {
        $isMaintenance = App::isDownForMaintenance() === true;
        $validated = $request->validate([
            'url'   => ['required', 'url'],
            'version' => ['required', 'string'],
            'shasum'    => ['required', 'string']
        ]);

        $this->appService->cleanUpdateDirectory($this->zipPath, $this->extractPath);

        try {
            $this->installerService->downloadPackage(config('adminui.licence_key'), $validated['url'], $this->zipPath);
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }
        try {
            $this->installerService->validatePackage($validated['shasum'], $this->zipPath);
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }

        // User could be in maintenance bypass mode, in which case, leave as is
        if (!$isMaintenance) {
            $bypassKey = $this->appService->down();
            $this->addOutput("Entering maintenance mode:", true);
            $this->addOutput($bypassKey);
        }

        try {
            AdminUIUpdate::update(fn ($line, $push = false) => $this->addOutput($line, $push), $validated['version'], $isMaintenance);
            return $this->sendSuccess();
        } catch (\Exception $e) {
            return $this->sendFailed($e->getMessage());
        }
    }

    public function refresh()
    {
        $this->dbService->migrateAndSeedUpdate();
        $this->appService->flushCache();
        Artisan::call('vendor:publish', [
            '--tag'      => 'adminui-public',
            '--force'    => true
        ]);
        return $this->sendSuccess("Site refreshed");
    }
}
