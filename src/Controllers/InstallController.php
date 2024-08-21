<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use AdminUI\AdminUIInstaller\Facades\Json;
use AdminUI\AdminUIInstaller\Actions\SeedDatabaseAction;
use AdminUI\AdminUIInstaller\Actions\RunMigrationsAction;
use AdminUI\AdminUIInstaller\Actions\UnpackReleaseAction;
use AdminUI\AdminUIInstaller\Actions\ComposerUpdateAction;
use AdminUI\AdminUIInstaller\Actions\SaveLicenceKeyAction;
use AdminUI\AdminUIInstaller\Actions\PublishResourcesAction;
use AdminUI\AdminUIInstaller\Actions\UpdateComposerFileAction;
use AdminUI\AdminUIInstaller\Actions\UpdateVersionEntryAction;
use AdminUI\AdminUIInstaller\Actions\DownloadLatestReleaseAction;
use AdminUI\AdminUIInstaller\Actions\CheckDatabaseConnectionAction;
use AdminUI\AdminUIInstaller\Actions\CreateLocalComposerFileAction;
use AdminUI\AdminUIInstaller\Actions\GetLatestReleaseDetailsAction;
use AdminUI\AdminUIInstaller\Actions\WriteLaravelElevenChangesAction;

class InstallController extends Controller
{
    public function index(CheckDatabaseConnectionAction $checkDb)
    {
        $hasDbConnection = $checkDb->execute();
        $isInstalled = Json::getField('installComplete');

        // if no database connection
        if ($hasDbConnection === false) {
            return view('adminui-installer::no-database');
        } elseif ($isInstalled === true) {
            return view('adminui-installer::already-installed');
        }

        return view('adminui-installer::index', [
            'status' => Json::get(),
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

    public function prepareDependencies(CreateLocalComposerFileAction $localAction, UpdateComposerFileAction $composerFileAction)
    {
        $localAction->execute();
        $composerFileAction->execute();
        Json::setField(field: 'prepareDependencies', data: true);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function updateDependencies(ComposerUpdateAction $action)
    {
        $action->execute();
        Json::setField(field: 'dependencies', data: true);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function publishResources(PublishResourcesAction $action)
    {
        $output = $action->execute();
        Json::setField(field: 'publishResources', data: true);
        Json::setField(field: 'publishResourcesLog', data: $output);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function runMigrations(RunMigrationsAction $action)
    {
        $output = $action->execute();
        Json::setField(field: 'runMigrations', data: true);
        Json::setField(field: 'runMigrationsLog', data: $output);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }

    public function seedDatabase(SeedDatabaseAction $action, UpdateVersionEntryAction $versionAction)
    {
        $output = $action->execute();
        Json::setField(field: 'seedDatabase', data: true);
        Json::setField(field: 'seedDatabaseLog', data: $output);

        $releaseDetails = Json::getField('releaseDetails');
        $versionAction->execute($releaseDetails['version']);
        Json::setField(field: 'installComplete', data: true);

        return response()->json(
            [
                'status' => Json::get(),
            ]
        );
    }
}
