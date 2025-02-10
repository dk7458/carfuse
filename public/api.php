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

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
logApiEvent("Request: {$method} {$requestUri}");

// Enforce global authentication and CSRF protection
enforceAuthentication();
if ($method === 'POST') {
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!validateCsrfToken($csrf)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid CSRF token']);
        logApiEvent("Failure: Invalid CSRF token");
        exit();
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
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API route not found']);
        logApiEvent("Failure: API route not found for '{$uri}'");
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Method not allowed']);
        logApiEvent("Failure: Method not allowed for '{$uri}'");
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        ob_start();
        include $handler;
        $output = ob_get_clean();
        header('Content-Type: application/json');
        echo json_encode(['data' => $output]);
        logApiEvent("Success: Routed to " . basename($handler));
        break;
}
?>
