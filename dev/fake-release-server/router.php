<?php

/**
 * Stand-in for management.adminui.co.uk, for `php -S` only.
 *
 *   GET /api/update          -> latest release details (+ `url` pointing back at this server)
 *   GET /download/adminui.zip -> the release zip
 *   POST /api/licence        -> licence check used by AdminUI itself (adminui/config/aui-setup.php `aui_endpoint`)
 *
 * Set FAKE_RELEASE_KEY to require a specific bearer token; otherwise any non-empty token is accepted.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$storage = __DIR__.'/storage';

$token = preg_replace('/^Bearer\s+/i', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
$expected = getenv('FAKE_RELEASE_KEY') ?: null;

if ($token === '' || ($expected !== null && ! hash_equals($expected, $token))) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Unauthenticated.']);

    return;
}

if ($path === '/api/update') {
    if (! is_file($storage.'/release.json')) {
        http_response_code(503);
        echo json_encode(['message' => 'No build found. Run: php build.php']);

        return;
    }
    $release = json_decode(file_get_contents($storage.'/release.json'), true);
    $scheme = ! empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $release['url'] = "$scheme://{$_SERVER['HTTP_HOST']}/download/adminui.zip";
    header('Content-Type: application/json');
    echo json_encode($release);

    return;
}

if ($path === '/api/licence') {
    // Mirrors the shape AdminUI's LicenceService reads. Licenses every package folder under src/Database/Migrations except
    // Staff, a legacy module whose migration references tables the CRM drop removed (it can't migrate on a fresh database).
    // Override with FAKE_LICENCE_PACKAGES="Accounts,Blog" to test a smaller licence.
    $packages = array_filter(explode(',', getenv('FAKE_LICENCE_PACKAGES') ?: 'Adminui,Accounts,Blog,Consent,Ecommerce,Pages,Stock,Subscriber,Support'));
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Licence valid (fake release server)',
        'data' => [
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'version' => 'v4.0.0-test',
            'next' => 0,
            'packages' => array_map(fn ($name) => ['name' => $name], array_values($packages)),
        ],
    ]);

    return;
}

if ($path === '/download/adminui.zip' && is_file($storage.'/adminui.zip')) {
    header('Content-Type: application/octet-stream');
    header('Content-Length: '.filesize($storage.'/adminui.zip'));
    readfile($storage.'/adminui.zip');

    return;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['message' => 'Not found']);
