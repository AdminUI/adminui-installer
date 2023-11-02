<?php

use Illuminate\Support\Facades\Route;
use AdminUI\AdminUIInstaller\Controllers\UpdateController;
use AdminUI\AdminUIInstaller\Controllers\InstallController;
use AdminUI\AdminUIInstaller\Controllers\RegisterController;
use AdminUI\AdminUIInstaller\Controllers\UninstallController;


Route::get('/install-adminui',                  [InstallController::class, 'index'])->name('adminui.installer.index');
Route::post('/install-adminui/download',        [InstallController::class, 'downloadInstaller'])->name('adminui.installer.download');
Route::post('/install-adminui/extract',         [InstallController::class, 'extractInstaller'])->name('adminui.installer.extract');
Route::post('/install-adminui/composer',        [InstallController::class, 'updateComposer'])->name('adminui.installer.dependencies');
Route::post('/install-adminui/base-publish',    [InstallController::class, 'basePublish'])->name('adminui.installer.base-publish');
Route::post('/install-adminui/base-migrations', [InstallController::class, 'baseMigrations'])->name('adminui.installer.base-migrations');
Route::post('/install-adminui/publish',         [InstallController::class, 'publish'])->name('adminui.installer.publish');
Route::post('/install-adminui/finish',          [InstallController::class, 'finishInstall'])->name('adminui.installer.finish');

Route::post('/install-adminui/clear-cache',          [InstallController::class, 'clearCache'])->name('adminui.installer.clear-cache');


Route::get('/install-adminui/register',         [RegisterController::class, 'index'])->name('adminui.installer.register');
Route::post('/install-adminui/register',        [RegisterController::class, 'store']);

Route::get('/update-adminui/check',             [UpdateController::class, 'checkUpdate'])->name('adminui.update.check');
Route::get('/update-adminui/refresh',           [UpdateController::class, 'refresh'])->name('adminui.update.refresh');
Route::post('/update-adminui',                  [UpdateController::class, 'updateSystem'])->name('adminui.update.install');
