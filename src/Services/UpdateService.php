<?php

namespace AdminUI\AdminUIInstaller\Services;

use Parsedown;
use ZipArchive;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Facades\AdminUIUpdate;
use AdminUI\AdminUIInstaller\Services\DatabaseService;
use AdminUI\AdminUIInstaller\Services\InstallerService;
use AdminUI\AdminUIInstaller\Services\ApplicationService;

class UpdateService
{
    /**
     * $zipPath - Path to use for the .zip installer relative to default Storage
     *
     * @var string
     */
    protected $zipPath = 'adminui-installer.zip';

    protected $extractPath = 'adminui-installer';


    public function __construct(
        protected ApplicationService $appService,
        protected DatabaseService $dbService,
        protected InstallerService $installerService
    ) {
    }

    public function getZipPath()
    {
        return $this->zipPath;
    }

    public function getExtractPath()
    {
        return $this->extractPath;
    }

    public function check()
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
            throw new \Exception('Can\'t update this copy of AdminUI since it appears to be outside the packages folder');
        } else if (file_exists(base_path('packages/adminui/.git')) === true) {
            throw new \Exception('Can\'t update this copy of AdminUI since it is under version control');
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

            return ['update' => $updateDetails, 'message' => 'There is a new version of AdminUI available!', 'isMajor' => $isMajor];
        } else {
            throw new \Exception("You are already using the latest version of AdminUI");
        }
    }

    public function update(callable $outputCallback, string $version, $isMaintenance = true)
    {
        $zipPath = Storage::path(AdminUIUpdate::getZipPath());

        $archive = new ZipArchive;
        if ($archive->open($zipPath) === true) {
            $outputCallback("Extract complete");

            $result = $this->installerService->installArchive($archive, $this->getExtractPath());
            $this->appService->checkForComposerUpdate($result['destination']);

            $dbOutput = $this->dbService->migrateAndSeedUpdate();
            foreach ($dbOutput as $line) {
                $outputCallback($line);
            }

            Artisan::call('vendor:publish', [
                '--tag'      => 'adminui-public',
                '--force'    => true
            ]);
            $outputCallback("Output:", true);
            $this->appService->flushCache();

            // Update the installed version in the database configurations table
            $this->installerService->updateVersionEntry($version);

            if (!$isMaintenance) {
                Artisan::call('up');
                $outputCallback("Exiting maintenance mode:", true);
            }
            $outputCallback("Install complete");

            return true;
        } else {
            throw new \Exception("There was a problem during installation. Please try again later");
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
}
