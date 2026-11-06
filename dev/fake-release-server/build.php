<?php

/**
 * Builds a release zip from a local AdminUI checkout and writes the manifest
 * that router.php serves as the "latest release" response.
 *
 * Usage: php build.php [--source=../../../adminui] [--version=v4.0.0] [--changelog=path.md]
 */
$opts = getopt('', ['source::', 'version::', 'changelog::']);

$source = realpath($opts['source'] ?? __DIR__.'/../../../adminui');
$version = $opts['version'] ?? 'v4.0.0';
$out = __DIR__.'/storage';

if ($source === false || ! is_file($source.'/composer.json')) {
    fwrite(STDERR, "Source is not an AdminUI checkout (no composer.json). Use --source=/path/to/adminui\n");
    exit(1);
}

// Excluded wherever they appear (nested node_modules are common in AdminUI's build tooling).
$excludeAnywhere = ['.git', 'node_modules'];
// Excluded only at the package root.
$excludeAtRoot = ['vendor', 'tests', '.env', 'run.lock', 'composer.local.json', '.idea', '.vscode'];

@mkdir($out, 0775, true);
$zipFile = $out.'/adminui.zip';
@unlink($zipFile);

$zip = new ZipArchive;
if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Could not create $zipFile\n");
    exit(1);
}

// The zip root must be the package contents (UnpackReleaseAction extracts straight into packages/adminui).
$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        function (SplFileInfo $file) use ($source, $excludeAnywhere, $excludeAtRoot) {
            $relative = substr($file->getPathname(), strlen($source) + 1);
            $segments = explode('/', $relative);

            return ! in_array($segments[0], $excludeAtRoot, true)
                && ! array_intersect($segments, $excludeAnywhere)
                && ! $file->isLink() // symlinks (e.g. pnpm links) can't be zipped reliably
                && substr($relative, -4) !== '.zip';
        }
    )
);

$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($source) + 1));
        $count++;
    }
}
$zip->close();

$changelog = isset($opts['changelog']) ? file_get_contents($opts['changelog']) : "## $version\n\n- Local test build";

file_put_contents($out.'/release.json', json_encode([
    'version' => $version,
    'changelog' => $changelog,
    'date' => date('Y-m-d H:i:s'),
    'shasum' => hash_file('sha256', $zipFile),
    'packages' => [],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

printf("Built %s (%d files, %.1f MB)\nsha256 %s\n", $zipFile, $count, filesize($zipFile) / 1048576, hash_file('sha256', $zipFile));
