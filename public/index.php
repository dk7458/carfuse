<?php
declare(strict_types=1);
header("Content-Type: text/html; charset=UTF-8");

// Load Core Files
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

// Load Routes
$dispatcher = require __DIR__ . '/../App/config/routes.php';

// Get the Requested URL Path
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Dispatch the Request
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        require __DIR__ . "/views/errors/404.php";
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo "Method Not Allowed";
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_callable($handler)) {
            call_user_func($handler, $vars);
        } else {
            http_response_code(500);
            echo "Invalid Route Handler";
        }
        break;
}
?>
