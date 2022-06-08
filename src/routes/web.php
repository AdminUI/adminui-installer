<?php

use Illuminate\Support\Facades\Route;
use AdminUI\AdminUIInstaller\Controllers\UpdateController;
use AdminUI\AdminUIInstaller\Controllers\InstallController;
use AdminUI\AdminUIInstaller\Controllers\RegisterController;
use AdminUI\AdminUIInstaller\Controllers\UninstallController;


Route::get('/install-adminui',                  [InstallController::class, 'index'])->name('adminui.installer.index');
Route::post('/install-adminui/download',        [InstallController::class, 'downloadInstaller'])->name('adminui.installer.one');
Route::post('/install-adminui/extract',         [InstallController::class, 'extractInstaller'])->name('adminui.installer.two');
Route::post('/install-adminui/composer',        [InstallController::class, 'updateComposer'])->name('adminui.installer.three');
Route::post('/install-adminui/base-publish',    [InstallController::class, 'basePublish'])->name('adminui.installer.four');
Route::post('/install-adminui/base-migrations', [InstallController::class, 'baseMigrations'])->name('adminui.installer.five');
Route::post('/install-adminui/publish',         [InstallController::class, 'publish'])->name('adminui.installer.six');
Route::post('/install-adminui/finish',          [InstallController::class, 'finishInstall'])->name('adminui.installer.seven');

Route::get('/install-adminui/register',         [RegisterController::class, 'index'])->name('adminui.installer.register');
Route::post('/install-adminui/register',        [RegisterController::class, 'store']);

// For development purposes only
// Route::get('/uninstall-adminui',                [UninstallController::class, 'index'])->name('adminui.uninstaller');

Route::get('/update-adminui/check',             [UpdateController::class, 'checkUpdate'])->name('adminui.update.check');
Route::post('/update-adminui',                  [UpdateController::class, 'updateSystem'])->name('adminui.update.install');
