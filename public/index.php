<?php
declare(strict_types=1);
header("Content-Type: text/html; charset=UTF-8");

// Debugging - Log Execution
error_reporting(E_ALL);
ini_set('display_errors', 1);
file_put_contents(__DIR__ . "/debug.log", "index.php started\n", FILE_APPEND);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

// Debug - Log Route Processing
file_put_contents(__DIR__ . "/debug.log", "Routes loading\n", FILE_APPEND);

// Load Routes
$dispatcher = require __DIR__ . '/config/routes.php';

// Get the Requested URL Path
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Debug - Log Requested URL
file_put_contents(__DIR__ . "/debug.log", "Request: $uri\n", FILE_APPEND);

// Dispatch the Request inside try-catch for error logging
try {
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    file_put_contents(__DIR__ . "/debug.log", "Dispatcher returned successfully\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents(__DIR__ . "/debug.log", "Dispatcher Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo "Internal Server Error";
    exit;
}

// Debug - Log Route Status
file_put_contents(__DIR__ . "/debug.log", "Route Info: " . print_r($routeInfo, true) . "\n", FILE_APPEND);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        require __DIR__ . "/views/errors/404.php";
        file_put_contents(__DIR__ . "/debug.log", "404 Triggered\n", FILE_APPEND);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo "Method Not Allowed";
        file_put_contents(__DIR__ . "/debug.log", "405 Triggered\n", FILE_APPEND);
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_callable($handler)) {
            file_put_contents(__DIR__ . "/debug.log", "Executing Handler directly\n", FILE_APPEND);
            try {
                call_user_func($handler, $vars);
            } catch (Exception $e) {
                file_put_contents(__DIR__ . "/debug.log", "Handler Exception: " . $e->getMessage() . "\n", FILE_APPEND);
                http_response_code(500);
                echo "Internal Server Error";
            }
        } elseif (is_array($handler) && class_exists($handler[0]) && method_exists($handler[0], $handler[1])) {
            file_put_contents(__DIR__ . "/debug.log", "Instantiating controller: " . $handler[0] . "\n", FILE_APPEND);
            $controller = new $handler[0]();
            try {
                call_user_func([$controller, $handler[1]], $vars);
            } catch (Exception $e) {
                file_put_contents(__DIR__ . "/debug.log", "Handler Exception: " . $e->getMessage() . "\n", FILE_APPEND);
                http_response_code(500);
                echo "Internal Server Error";
            }
        } else {
            http_response_code(500);
            echo "Invalid Route Handler";
            file_put_contents(__DIR__ . "/debug.log", "500 Error: Invalid Handler\n", FILE_APPEND);
        }
        break;
}

// Debug - Log End of Execution
file_put_contents(__DIR__ . "/debug.log", "index.php execution complete\n", FILE_APPEND);
?>
