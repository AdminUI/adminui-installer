<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use AdminUI\AdminUIInstaller\Services\AssetService;
use Illuminate\Routing\Controller;

/**
 * Serves the installer's compiled assets straight from the package, so nothing has to be published first.
 */
class AssetController extends Controller
{
    protected const TYPES = [
        'js' => 'text/javascript; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'webp' => 'image/webp',
        'map' => 'application/json',
    ];

    public function __invoke(string $path)
    {
        $root = realpath(AssetService::buildPath());
        $file = $root ? realpath($root.'/'.$path) : false;
        $type = self::TYPES[strtolower(pathinfo((string) $file, PATHINFO_EXTENSION))] ?? null;

        // Only real files inside the build directory, and only types the build produces
        if ($file === false || $type === null || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR) || ! is_file($file)) {
            abort(404);
        }

        // File names are content-hashed, so they can be cached for good
        return response()->file($file, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
