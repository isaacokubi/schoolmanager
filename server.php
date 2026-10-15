<?php

// PRESENTATION NOTE: PHP's built-in development server entry point.
// This script routes requests for existing static files to the web server and
// sends application requests through Laravel's public front controller.

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
