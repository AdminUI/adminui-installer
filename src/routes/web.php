<?php

use Illuminate\Support\Facades\Route;
use AdminUI\AdminUIInstaller\Controllers\InstallController;
use AdminUI\AdminUIInstaller\Controllers\UninstallController;


Route::get('/install-adminui', [InstallController::class, 'index'])->name('adminui.installer.index');

Route::post('/install-adminui/download', [InstallController::class, 'downloadInstaller'])->name('adminui.installer.one');
Route::post('/install-adminui/extract',  [InstallController::class, 'extractInstaller'])->name('adminui.installer.two');
Route::post('/install-adminui/composer', [InstallController::class, 'updateComposer'])->name('adminui.installer.three');
Route::post('/install-adminui/publish',  [InstallController::class, 'publish'])->name('adminui.installer.four');
Route::post('/install-adminui/finish',   [InstallController::class, 'finishInstall'])->name('adminui.installer.five');


Route::get('/uninstall-adminui',   [UninstallController::class, 'index'])->name('adminui.uninstaller');
