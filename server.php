<?php

// Parse the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Rules from root .htaccess
if (preg_match('~^/(App/Controllers|App/Services|config|vendor|logs)~', $uri)) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

// Handle static files from public directory
if (file_exists(__DIR__ . '/public' . $uri)) {
    // For CSS, JS, images, etc.
    return false;
}

// Handle special public views (mimics public/.htaccess rule)
if (preg_match('~^/(home|auth/login|vehicles|auth/register)$~', $uri)) {
    // Note: The script will still go through index.php which should handle view loading
}

// Handle API requests
if (strpos($uri, '/api/') === 0) {
    // API requests will be handled by FastRoute in index.php
    // No need to do anything special here
}

// Forward everything else to public/index.php
require_once __DIR__ . '/public/index.php';