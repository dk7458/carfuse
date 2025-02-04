<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load Route Dispatcher
$dispatcher = require __DIR__ . '/../routes/web.php';

// Fetch HTTP method and URI
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Ensure URI is properly parsed
$uri = parse_url($uri, PHP_URL_PATH);
$uri = rtrim($uri, '/'); // Normalize to avoid trailing slashes issues

// Log incoming request (optional for debugging)
error_log("Incoming Request: Method: $httpMethod, URI: $uri");

// Check if root URL (/) should load the landing page
if ($uri === '' || $uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/../App/Views/landing.php';
    exit;
}

// Dispatch the request
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo "404 Not Found - `$uri` does not exist.";
        error_log("404 Not Found: $uri");
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo "405 Method Not Allowed.";
        error_log("405 Method Not Allowed: $uri");
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_callable($handler)) {
            call_user_func_array($handler, $vars);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;
            if (class_exists($controller) && method_exists($controller, $method)) {
                $instance = new $controller();
                call_user_func_array([$instance, $method], $vars);
            } else {
                http_response_code(500);
                echo "500 Internal Server Error: Controller or Method Not Found.";
                error_log("500 Error: Controller or Method Not Found: $controller@$method");
            }
        } else {
            http_response_code(500);
            echo "500 Internal Server Error: Invalid Route Handler.";
            error_log("500 Error: Invalid Route Handler: $uri");
        }
        break;
}
