<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class BaseInstallController extends Controller
{
    /**
     * $zipPath - Path to use for the .zip installer relative to default Storage
     *
     * @var string
     */
    protected $zipPath = 'adminui-installer.zip';
    /**
     * $extractPath - Path to use for the extracted installer relative to default Storage
     *
     * @var string
     */
    protected $extractPath = 'adminui-installer';
    /**
     * $output - Holds the log output from executing installs or updates
     *
     * @var array
     */
    protected $output = [];


    /**
     * addOutput - Adds an entry into the install log
     *
     * @param  string $intro - The text to put at the beginning of the output line
     * @param  bool $artisan - If true, the output from the last Artisan::call will be appended
     * @param  string $logData - Any additional data to log
     * @return void
     */
    protected function addOutput($intro = "", $artisan = false, $logData = "")
    {
        $line = $intro;
        if (true === $artisan) {
            $line .= " " . $this->cleanOutput(Artisan::output());
        }
        if (!empty($logData)) {
            $line .= " " . $this->cleanOutput($logData);
        }
        $this->output[] = $line;
    }

    /**
     * cleanOutput - Concatenates multiple lines into one for readability
     *
     * @param  string $output
     * @return string
     */
    protected function cleanOutput(String $output)
    {
        return str_replace(PHP_EOL, ' ', $output);
    }

    protected function sendSuccess($data = null)
    {
        return response()->json([
            'status' => 'success',
            'log'   => $this->output,
            'data'  => $data
        ]);
    }

    protected function sendFailed(string $errorMessage, string $status = "failed")
    {
        return response()->json([
            'status' => $status,
            'error' => $errorMessage,
            'log'   => $this->output
        ]);
    }
}
