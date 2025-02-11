<?php
define('API_ENTRY', true);
date_default_timezone_set('UTC');

require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../App/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ✅ Log every API request
function logApiEvent($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/../logs/api.log';
    file_put_contents($logFile, "{$timestamp} - {$message}\n", FILE_APPEND);
}

// ✅ Standardized JSON response function
function sendJsonResponse($status, $message, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

// ✅ Extract JWT from Authorization Header or Cookie
function getJWT() {
    $headers = getallheaders();
    if (isset($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        return $matches[1];
    }
    return $_COOKIE['jwt'] ?? null;
}

// ✅ Validate JWT if required
function requireAuthIfProtected($apiPath) {
    $protectedRoutes = ['dashboard', 'profile', 'reports'];
    if (in_array($apiPath, $protectedRoutes)) {
        AuthMiddleware::validateJWT(true);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
logApiEvent("Request: {$method} {$requestUri}");

// ✅ Parse API request
$apiPath = str_replace('/api/', '', parse_url($requestUri, PHP_URL_PATH));
requireAuthIfProtected($apiPath);

// ✅ CSRF Protection for Authenticated POST Requests
if ($method === 'POST' && getJWT()) {
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!validateCsrfToken($csrf)) {
        logApiEvent("CSRF validation failed: {$apiPath}");
        sendJsonResponse('error', 'Invalid CSRF token', [], 403);
    }
}

// ✅ Register API endpoints dynamically
$apiDir = __DIR__ . '/api';
$apiRoutes = [];

if (is_dir($apiDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apiDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = ltrim(str_replace([$apiDir, '\\'], ['', '/'], $file->getPathname()), '/');
            $route = preg_replace('/\.php$/', '', $relativePath);
            $apiRoutes[$route] = $file->getPathname();
        }
    }
}

// ✅ Setup FastRoute dispatcher
$dispatcher = simpleDispatcher(function (RouteCollector $router) use ($apiRoutes) {
    foreach ($apiRoutes as $route => $filePath) {
        $router->addRoute(['GET', 'POST'], '/' . $route, $filePath);
    }
});

// ✅ Route request
$routeInfo = $dispatcher->dispatch($method, "/{$apiPath}");

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        sendJsonResponse('error', 'API route not found', [], 404);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        sendJsonResponse('error', 'Method not allowed', [], 405);
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        ob_start();
        include $handler;
        $output = ob_get_clean();
        sendJsonResponse('success', 'Request successful', ['data' => json_decode($output, true) ?: $output], 200);
        break;
}

logApiEvent("Unhandled API request: {$requestUri}");
sendJsonResponse('error', 'Unhandled API request', [], 500);
