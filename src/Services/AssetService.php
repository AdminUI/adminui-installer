<?php

namespace AdminUI\AdminUIInstaller\Services;

use Illuminate\Support\HtmlString;

/**
 * The installer's compiled Vite assets ship inside this package (public/build) and are served by AssetController,
 * so the install page works as soon as the package is required, without publishing anything to the app.
 */
class AssetService
{
    protected const ENTRIES = ['resources/css/app.css', 'resources/js/app.js'];

    public static function buildPath(string $path = ''): string
    {
        return dirname(__DIR__, 2).'/public/build'.($path !== '' ? '/'.ltrim($path, '/') : '');
    }

    /**
     * Tags for the entry points, read from the build manifest
     */
    public static function tags(): HtmlString
    {
        $manifestPath = self::buildPath('manifest.json');

        if (! is_file($manifestPath)) {
            return new HtmlString('<!-- AdminUI installer assets have not been built -->');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        $styles = [];
        $scripts = [];

        foreach (self::ENTRIES as $entry) {
            if (! isset($manifest[$entry])) {
                continue;
            }

            self::collectStyles($manifest, $entry, $styles);

            $file = $manifest[$entry]['file'];
            if (str_ends_with($file, '.css')) {
                $styles[$file] = true;
            } else {
                $scripts[$file] = true;
            }
        }

        $tags = '';
        foreach (array_keys($styles) as $file) {
            $tags .= '<link rel="stylesheet" href="'.e(self::url($file)).'">'."\n";
        }
        foreach (array_keys($scripts) as $file) {
            $tags .= '<script type="module" src="'.e(self::url($file)).'"></script>'."\n";
        }

        return new HtmlString($tags);
    }

    public static function url(string $file): string
    {
        return route('adminui.installer.assets', ['path' => $file], false);
    }

    /**
     * CSS pulled in by a JS entry and by the chunks it imports
     */
    protected static function collectStyles(array $manifest, string $key, array &$styles, array &$seen = []): void
    {
        if (isset($seen[$key]) || ! isset($manifest[$key])) {
            return;
        }
        $seen[$key] = true;

        foreach ($manifest[$key]['css'] ?? [] as $css) {
            $styles[$css] = true;
        }
        foreach ($manifest[$key]['imports'] ?? [] as $import) {
            self::collectStyles($manifest, $import, $styles, $seen);
        }
    }
}
