<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use AdminUI\AdminUIInstaller\Actions\CheckDatabaseConnectionAction;
use AdminUI\AdminUIInstaller\Actions\CheckRegistrationStatusAction;
use AdminUI\AdminUIInstaller\Actions\ComposerUpdateAction;
use AdminUI\AdminUIInstaller\Actions\CreateLocalComposerFileAction;
use AdminUI\AdminUIInstaller\Actions\DownloadLatestReleaseAction;
use AdminUI\AdminUIInstaller\Actions\EnableCacheSerializationAction;
use AdminUI\AdminUIInstaller\Actions\GetLatestReleaseDetailsAction;
use AdminUI\AdminUIInstaller\Actions\PublishResourcesAction;
use AdminUI\AdminUIInstaller\Actions\RunMigrationsAction;
use AdminUI\AdminUIInstaller\Actions\SaveLicenceKeyAction;
use AdminUI\AdminUIInstaller\Actions\SeedDatabaseAction;
use AdminUI\AdminUIInstaller\Actions\SetupWorkspaceAction;
use AdminUI\AdminUIInstaller\Actions\UnpackReleaseAction;
use AdminUI\AdminUIInstaller\Actions\UpdateComposerFileAction;
use AdminUI\AdminUIInstaller\Actions\UpdateVersionEntryAction;
use AdminUI\AdminUIInstaller\Facades\Json;
use AdminUI\AdminUIInstaller\Traits\StreamsStepOutput;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InstallController extends Controller
{
    use StreamsStepOutput;

    public function index(CheckDatabaseConnectionAction $checkDb, CheckRegistrationStatusAction $registration)
    {
        $hasDbConnection = $checkDb->execute();
        $isInstalled = Json::getField('installComplete');

        // The SPA renders every screen, the server only decides which one it opens on
        $page = match (true) {
            $hasDbConnection === false => 'no-database',
            $isInstalled === true => 'already-installed',
            default => 'install',
        };

        return view('adminui-installer::index', [
            'page' => $page,
            'status' => Json::get(),
            // The register screen must stay closed once an admin exists, even if it is opened by URL hash
            'registered' => $hasDbConnection && $registration->execute() === CheckRegistrationStatusAction::ALREADY_REGISTERED,
        ]);
    }

    public function saveKey(Request $request, SaveLicenceKeyAction $action)
    {
        $validated = $request->validate([
            'licence_key' => ['required', 'string'],
        ]);

        sleep(2);

        $action->execute($validated['licence_key']);

        Json::setField(field: 'saveKey', data: true);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function getLatestReleaseDetails(GetLatestReleaseDetailsAction $action)
    {
        $action->execute();

        sleep(5);

        Json::setField('getLatestReleaseDetails', true);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function downloadRelease(DownloadLatestReleaseAction $action)
    {
        $action->execute();

        sleep(5);

        Json::setField('downloadRelease', true);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function unpackRelease(UnpackReleaseAction $action)
    {
        $action->execute();

        Json::setField(field: 'unpackRelease', data: true);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function prepareDependencies(
        CreateLocalComposerFileAction $localAction,
        UpdateComposerFileAction $composerFileAction,
        SetupWorkspaceAction $workspaceAction,
        EnableCacheSerializationAction $cacheAction
    ) {
        $localAction->execute();
        $composerFileAction->execute();
        $workspaceAction->execute();
        $cacheAction->execute();
        Json::setField(field: 'prepareDependencies', data: true);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function updateDependencies(ComposerUpdateAction $action)
    {
        return $this->streamStep(function (callable $log) use ($action) {
            $action->execute($log);
            Json::setField(field: 'dependencies', data: true);
        });
    }

    public function publishResources(PublishResourcesAction $action)
    {
        return $this->streamStep(function (callable $log) use ($action) {
            $output = $action->execute($log);
            Json::setField(field: 'publishResources', data: true);
            Json::setField(field: 'publishResourcesLog', data: $output);
        });
    }

    public function runMigrations(RunMigrationsAction $action)
    {
        return $this->streamStep(function (callable $log) use ($action) {
            $output = $action->execute(false, $log);
            Json::setField(field: 'runMigrations', data: true);
            Json::setField(field: 'runMigrationsLog', data: $output);
        });
    }

    public function seedDatabase(SeedDatabaseAction $action, UpdateVersionEntryAction $versionAction)
    {
        return $this->streamStep(function (callable $log) use ($action, $versionAction) {
            $log("Seeding database...\n");
            $output = $action->execute();
            Json::setField(field: 'seedDatabase', data: true);
            Json::setField(field: 'seedDatabaseLog', data: $output);

            $releaseDetails = Json::getField('releaseDetails');
            $versionAction->execute($releaseDetails['version']);
            Json::setField(field: 'installComplete', data: true);
            $log("Database seeded.\n");
        });
    }
}
