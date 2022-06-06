<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

class RegisterController extends BaseInstallController
{

    public function index()
    {
        return view('adminui-installer::register');
    }
}
