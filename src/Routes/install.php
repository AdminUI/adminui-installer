<?php

use AdminUI\AdminUIInstaller\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::prefix('install-adminui')->as('adminui.installer.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');

    Route::post('save-key', [InstallController::class, 'saveKey'])->name('save-key');
    Route::post('release-details', [InstallController::class, 'getLatestReleaseDetails'])->name('release-details');
    Route::post('download-release', [InstallController::class, 'downloadRelease'])->name('download-release');
    Route::post('unpack-release', [InstallController::class, 'unpackRelease'])->name('unpack-release');
    Route::post('prepare-dependencies', [InstallController::class, 'prepareDependencies'])->name('prepare-dependencies');
    Route::post('dependencies', [InstallController::class, 'updateDependencies'])->name('dependencies');
    Route::post('publish-resources', [InstallController::class, 'publishResources'])->name('publish-resources');
    Route::post('run-migrations', [InstallController::class, 'runMigrations'])->name('run-migrations');
    Route::post('seed-database', [InstallController::class, 'seedDatabase'])->name('seed-database');
});
