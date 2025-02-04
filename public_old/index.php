<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

// Load Route Dispatcher
$dispatcher = require __DIR__ . '/../routes/web.php';

// Fetch method and URI
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
$uri = parse_url($uri, PHP_URL_PATH);
$uri = rtrim($uri, '/'); // Normalize URI to prevent trailing slash issues

// If root URL (/) load the landing page
if ($uri === '' || $uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/../App/Views/landing.php';
    exit;
}

// Dispatch the request
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
var_dump($routeInfo);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo "404 Not Found - `$uri` does not exist.";
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo "405 Method Not Allowed.";
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
            }
        } else {
            http_response_code(500);
            echo "500 Internal Server Error: Invalid Route Handler.";
        }
        break;
}
