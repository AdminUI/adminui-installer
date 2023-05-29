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
use AdminUI\AdminUIInstaller\Events\UpdateProgress;
use Exception;

class UpdateNextController extends BaseInstallController
{
    private int $step = 0;
    private int $total = 6;

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
        $installedVersion = \AdminUI\AdminUI\Models\Configuration::where('name', 'installed_version')->firstOrCreate(
            ['name'  => 'installed_version'],
            [
                'label' => 'Installed Version',
                'value' => 'v0.0.1',
                'section' => 'private',
                'type'  => 'text'
            ]
        );

        if (file_exists(base_path('packages/adminui')) === false) {
            return $this->sendFailed('Can\'t update this copy of AdminUI since it appears to be outside the packages folder');
        } else if (file_exists(base_path('packages/adminui/.git')) === true) {
            //  return $this->sendFailed('Can\'t update this copy of AdminUI since it is under version control');
        }

        // Fetch the available version from the MGMT server
        $updateDetails = $this->installerService->checkLatestRelease(config('adminui.licence_key'));

        // Check if update is available
        $updateIsAvailable = version_compare($updateDetails['version'], $installedVersion->value, '>');


        if (true === $updateIsAvailable) {
            // Calculate if this is a major update for the purpose of warning the user
            $availableMajor = $this->getMajor($updateDetails['version']);
            $installedMajor = $this->getMajor($installedVersion->value);
            $isMajor = $availableMajor > $installedMajor;
            // Parse the .md format changelog into HTML
            $Parsedown = new Parsedown();
            $updateDetails['changelog'] = $Parsedown->text($updateDetails['changelog']);

            return $this->sendSuccess(['update' => $updateDetails, 'message' => 'There is a new version of AdminUI available!', 'isMajor' => $isMajor]);
        } else {
            return $this->sendFailed("You are already using the latest version of AdminUI", "done");
        }
    }

    /**
     * getMajor - Extract the MAJOR version number from a semantic versioning string
     *
     * @param  string $version
     * @return int
     */
    private function getMajor(string $version): int
    {
        return preg_match('/v?(\d+)\.(\d+)/', $version);
    }

    /**
     * updateSystem - Route controller for installing an update
     *
     * @param  mixed $request
     * @return void
     */
    public function updateSystem(Request $request)
    {

        $validated = $request->validate([
            'url'   => ['required', 'url'],
            'version' => ['required', 'string'],
            'shasum'    => ['required', 'string']
        ]);
        $this->start("preparing", "Preparing update");
        $isMaintenance = App::isDownForMaintenance() === true;

        $this->appService->cleanUpdateDirectory($this->zipPath, $this->extractPath);
        $this->finish("preparing");

        try {
            $this->start("downloading", "Downloading files");
            $this->installerService->downloadPackage(config('adminui.licence_key'), $validated['url'], $this->zipPath);
            $this->finish("downloading");
        } catch (\Exception $e) {
            $this->error("downloading", $e->getMessage());
            return $this->sendFailed("failed");
        }
        try {
            $this->start("validating", "Validating downloaded files");
            $this->installerService->validatePackage($validated['shasum'], $this->zipPath);
            $this->finish("validating");
        } catch (\Exception $e) {
            $this->error("validating", $e->getMessage());
            return $this->sendFailed("failed");
        }

        // User could be in maintenance bypass mode, in which case, leave as is
        if (!$isMaintenance) {
            $this->total++;
            $this->start("down", "Entering maintenance mode...");
            $bypassKey = $this->appService->down();
            $url = config('app.url') . "/" . $bypassKey;
            $this->append("down", "Bypass URL is <a href='" . $url . "' target='_blank'>" . $url . "</a>", true);
        }

        $zipPath = Storage::path($this->zipPath);

        $archive = new ZipArchive;
        $this->start("extracting", "Extracting files...");

        if ($archive->open($zipPath) === true) {
            $result = $this->installerService->installArchive($archive, $this->extractPath);
            $this->finish("extracting");

            $this->total++;
            $this->start("composer", "Checking for dependency updates...");
            try {
                $this->appService
                    ->checkForComposerUpdate($result['destination'], fn ($o) => $this->details("composer", $o));
                $this->finish("composer");
            } catch (\Exception $e) {
                $this->error("composer", $e->getMessage());
                return $this->sendFailed("failed");
            }

            $this->start("migrate", "Updating database...");
            $dbOutput = $this->dbService->migrateAndSeedUpdate();
            foreach ($dbOutput as $line) {
                $this->append("migrate", $line);
            }
            $this->finish("migrate");

            $this->start("publish", "Refreshing assets");
            Artisan::call('vendor:publish', [
                '--tag'      => 'adminui-public',
                '--force'    => true
            ]);
            $this->finish("publish");

            $this->start("clean", "Cleaning up");
            $this->appService->flushCache();

            // Update the installed version in the database configurations table
            $this->installerService->updateVersionEntry($validated['version']);
            $this->finish("clean");


            if (!$isMaintenance) {
                $this->total++;
                $this->start("up", "Exiting maintenance mode");
                Artisan::call('up');
                $this->finish("up", "status");
            }
            return $this->sendSuccess();
        } else {
            $this->error("extracting", "Failed to open installation files. Please try again later");
            return $this->sendFailed("Failed");
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

    private function status(array $data)
    {
        broadcast(new UpdateProgress($data));
    }

    private function start(string $key, string $message)
    {
        $this->status([
            'key' => $key,
            'step' => $this->step++,
            'msg' => $message,
            'total' => $this->total
        ]);
    }

    private function append(string $key, string $message, bool $done = false)
    {
        $this->status([
            "key" => $key,
            "msg" => "<br><small class='ml-4'>" . $message . "</small>",
            "status" => $done === true ? "done" : null
        ]);
    }

    private function finish(string $key): void
    {
        $this->status(["key" => $key, "status" => "done"]);
    }

    private function error(string $key, $error): void
    {
        $this->status(["key" => $key, "status" => "fail", "error" => $error]);
    }

    private function details(string $key, string $details, bool $done = false)
    {
        $this->status([
            "key" => $key,
            "details" => $details,
            "status" => $done === true ? "done" : null
        ]);
    }
}
