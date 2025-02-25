<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load dependency container
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Load Bootstrap (Dependencies, Configs, Logger, DB)
$bootstrap = require_once __DIR__ . '/../bootstrap.php';
// Replace bootstrap logger with centralized logger
$logger = getLogger('api');
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
        } elseif (is_string($handler) && strpos($handler, '@') !== false) {
            list($class, $method) = explode('@', $handler, 2);
            $controller = $container->get($class);
            if (method_exists($controller, $method)) {
                $controller->{$method}($vars);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Controller method not found"]);
            }
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
