<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load Route Dispatcher
$dispatcher = require __DIR__ . '/../routes/web.php';

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/'); // Normalize URI

// Load Landing Page for Root URL
if ($uri === '' || $uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/../App/Views/landing.php';
    exit;
}

// Dispatch Route
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(["error" => "404 Not Found - `$uri` does not exist."]);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(["error" => "405 Method Not Allowed."]);
        break;

    case FastRoute\Dispatcher::FOUND:
        [$controller, $method] = $routeInfo[1];

        if (class_exists($controller) && method_exists($controller, $method)) {
            $instance = new $controller();
            call_user_func_array([$instance, $method], $routeInfo[2]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "500 Internal Server Error: Controller or Method Not Found."]);
        }
        break;
}
