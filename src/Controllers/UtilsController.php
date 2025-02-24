<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use AdminUI\AdminUIInstaller\Traits\SlimJsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class UtilsController extends Controller
{
    use SlimJsonResponse;

    public function clearCache()
    {
        Artisan::call('optimize:clear');
       // Artisan::call('optimize');

        return $this->sendSuccess();
    }
}
