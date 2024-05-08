<?php

namespace AdminUI\AdminUIInstaller\Traits;

trait SlimJsonResponse
{
    public function sendSuccess(array|string|null $data = null, array $log = [])
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'log' => $log,
        ]);
    }

    public function sendFailed(string $errorMessage, array $log = [])
    {
        return response()->json([
            'status' => 'failed',
            'error' => $errorMessage,
            'log' => $log,
        ]);
    }
}
