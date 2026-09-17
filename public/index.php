<?php

define('LARAVEL_START', microtime(true));

// The public website historically renders media as /storage/{path}.
// When the public disk is S3-compatible in production, redirect those
// legacy URLs to the configured object-storage URL so existing database
// records continue to work without rewriting every Blade template.
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$storagePrefix = '/storage/';
$publicDiskDriver = strtolower(trim((string) getenv('PUBLIC_DISK_DRIVER')));
$publicStorageUrl = rtrim(trim((string) (getenv('PUBLIC_DISK_URL') ?: getenv('AWS_URL'))), '/');

if ($publicDiskDriver === 's3' && $publicStorageUrl !== '' && str_starts_with($uri, $storagePrefix)) {
    $relativePath = ltrim(substr($uri, strlen($storagePrefix)), '/');
    if ($relativePath !== '') {
        $segments = array_map('rawurlencode', explode('/', $relativePath));
        $target = $publicStorageUrl . '/' . implode('/', $segments);

        if (!empty($_SERVER['QUERY_STRING'])) {
            $target .= '?' . $_SERVER['QUERY_STRING'];
        }

        header('Cache-Control: public, max-age=3600, s-maxage=86400');
        header('Location: ' . $target, true, 302);
        exit;
    }
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
