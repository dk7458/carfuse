<?php
define('API_ENTRY', true);
date_default_timezone_set('UTC');

require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

// Log every API event
function logApiEvent($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/../logs/api.log';
    file_put_contents($logFile, "{$timestamp} - {$message}\n", FILE_APPEND);
}

// New helper function to send standardized JSON responses
function sendJsonResponse($status, $data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'data' => $data]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
logApiEvent("Request: {$method} {$requestUri}");

// Determine the route path and public routes
$publicRoutes = ['/auth/login', '/auth/register'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Enforce authentication for protected routes
if (!in_array($path, $publicRoutes)) {
    // Assuming isAuthenticated() is defined in SecurityHelper.php
    if (!isAuthenticated()) {
        $authLogFile = __DIR__ . '/../logs/auth.log';
        file_put_contents($authLogFile, date('Y-m-d H:i:s') . " - Authentication failure for {$path}\n", FILE_APPEND);
        sendJsonResponse('error', ['message' => 'Authentication required'], 401);
    }
}

// Global CSRF check for POST requests
if ($method === 'POST') {
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!validateCsrfToken($csrf)) {
        $authLogFile = __DIR__ . '/../logs/auth.log';
        file_put_contents($authLogFile, date('Y-m-d H:i:s') . " - CSRF token validation failed for {$path}\n", FILE_APPEND);
        sendJsonResponse('error', ['message' => 'Invalid CSRF token'], 403);
    }
}

// Dynamically register API routes from /public/api directory
$apiDir = __DIR__ . '/api';
$apiRoutes = [];
if (is_dir($apiDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($apiDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace($apiDir, '', $file->getPathname());
            $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
            $route = preg_replace('/\.php$/', '', $relativePath);
            $apiRoutes[$route] = $file->getPathname();
        }
    }
}

// Setup FastRoute dispatcher
$dispatcher = simpleDispatcher(function (RouteCollector $router) use ($apiRoutes) {
    foreach ($apiRoutes as $route => $filePath) {
        $router->addRoute('GET', '/' . $route, $filePath);
        $router->addRoute('POST', '/' . $route, $filePath);
    }
});

// Remove query string from URI and dispatch routing
$uri = parse_url($requestUri, PHP_URL_PATH);
$routeInfo = $dispatcher->dispatch($method, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        sendJsonResponse('error', ['message' => 'API route not found'], 404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        sendJsonResponse('error', ['message' => 'Method not allowed'], 405);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        ob_start();
        include $handler;
        $output = ob_get_clean();
        // Wrap output in JSON response, ensuring success status and HTTP 200
        sendJsonResponse('success', ['data' => $output], 200);
        break;
}
?>
