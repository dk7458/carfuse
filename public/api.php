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

// ✅ Public vs. Protected API Route Handling
function requireAuthIfProtected($apiPath) {
    $protectedRoutes = ['dashboard', 'profile', 'reports'];
    if (in_array($apiPath, $protectedRoutes)) {
        SecurityHelper::validateJWT(true);
    }
}

// ✅ Capture Incoming API Request
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
logApiEvent("Request: {$method} {$requestUri}");

// ✅ Parse API Path
$apiPath = trim(str_replace('/api/', '', parse_url($requestUri, PHP_URL_PATH)), '/');
requireAuthIfProtected($apiPath);

// ✅ CSRF Protection for Authenticated POST Requests
if ($method === 'POST' && getJWT()) {
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!validateCsrfToken($csrf)) {
        logApiEvent("CSRF validation failed: {$apiPath}");
        sendJsonResponse('error', 'Invalid CSRF token', [], 403);
    }
}

// ✅ Dynamically Register API Endpoints
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

// ✅ Setup FastRoute Dispatcher
$dispatcher = simpleDispatcher(function (RouteCollector $router) use ($apiRoutes) {
    foreach ($apiRoutes as $route => $filePath) {
        $router->addRoute(['GET', 'POST'], '/' . $route, $filePath);
    }
});

// ✅ Route API Request
$routeInfo = $dispatcher->dispatch($method, "/{$apiPath}");

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        logApiEvent("404 API Not Found: {$apiPath}");
        sendJsonResponse('error', 'API route not found', [], 404);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        logApiEvent("405 Method Not Allowed: {$apiPath}");
        sendJsonResponse('error', 'Method not allowed', [], 405);
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        ob_start();
        include $handler;
        $output = ob_get_clean();

        // ✅ Validate JSON Output
        $jsonOutput = json_decode($output, true);
        if ($jsonOutput === null && json_last_error() !== JSON_ERROR_NONE) {
            logApiEvent("Invalid JSON Response from {$apiPath}");
            sendJsonResponse('error', 'Invalid response format', [], 500);
        }

        sendJsonResponse('success', 'Request successful', ['data' => $jsonOutput ?: $output], 200);
        break;
}

logApiEvent("Unhandled API request: {$requestUri}");
sendJsonResponse('error', 'Unhandled API request', [], 500);
