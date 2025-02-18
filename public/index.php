<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Load Bootstrap (Dependencies, Configs, Logger, DB)
$bootstrap = require_once __DIR__ . '/../bootstrap.php';
$logger = $bootstrap['logger'];
$container = $bootstrap['container'];

// ✅ Get Requested URI & HTTP Method
$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$requestMethod = $_SERVER['REQUEST_METHOD'];

// ✅ Route API Requests Using FastRoute
$dispatcher = require __DIR__ . '/../config/routes.php';
$routeInfo = $dispatcher->dispatch($requestMethod, "/$requestUri");

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_callable($handler)) {
            call_user_func($handler, $vars);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Handler not callable"]);
        }
        break;

    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(["error" => "Route not found"]);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        break;
}
?>
