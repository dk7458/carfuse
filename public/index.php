<?php

declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php'; // Ensure the bootstrap file is included

require_once __DIR__ . '/../vendor/autoload.php';
header("Content-Type: text/html; charset=UTF-8");

// Set response headers
header('Content-Type: application/json');

// Load the router
$dispatcher = require __DIR__ . '/../config/routes.php';

// Normalize request URI
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Redirect root URL to landing page
if ($uri === '' || $uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/../App/Views/landing.php';
    exit;
}

// Dispatch the request
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(["error" => "404 Not Found"]);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(["error" => "405 Method Not Allowed"]);
        break;

    case FastRoute\Dispatcher::FOUND:
        [$controller, $method] = $routeInfo[1];
        $params = $routeInfo[2];

        // Check if the controller class exists
        if (!class_exists($controller)) {
            http_response_code(500);
            echo json_encode(["error" => "500 Internal Server Error", "message" => "Controller not found: $controller"]);
            exit;
        }

        $instance = new $controller();

        // Check if the method exists in the controller
        if (!method_exists($instance, $method)) {
            http_response_code(500);
            echo json_encode(["error" => "500 Internal Server Error", "message" => "Method not found: $method"]);
            exit;
        }

        // Execute the controller method with parameters
        call_user_func_array([$instance, $method], $params);
        break;
}
