<?php
// ✅ Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Load Bootstrap & Dependencies
$bootstrap = require_once __DIR__ . '/../bootstrap.php';

// ✅ Ensure Logger is Set
if (!isset($bootstrap['logger']) || !$bootstrap['logger'] instanceof Psr\Log\LoggerInterface) {
    die("❌ Fatal Error: Logger must be an instance of LoggerInterface.");
}
$logger = $bootstrap['logger'];

// ✅ Get Requested URI & Log Request
$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$logger->info("[ROUTER] Incoming request: /$requestUri");

// ✅ Load FastRoute Dispatcher
$dispatcher = require __DIR__ . '/../config/routes.php';

// ✅ Ensure Dispatcher is Valid
if (!$dispatcher instanceof FastRoute\Dispatcher) {
    $logger->error("FastRoute dispatcher is not valid.");
    die("❌ FastRoute dispatcher is not valid.");
}

// ✅ Route Request
$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], "/$requestUri");

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_callable($handler)) {
            $logger->info("Executing handler for route: /$requestUri");
            call_user_func($handler, $vars);
        } else {
            http_response_code(500);
            $logger->error("Handler not callable for route: /$requestUri");
            require __DIR__ . "/../public/views/errors/500.php";
        }
        exit;

    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        $logger->error("404 Not Found: /$requestUri");
        require __DIR__ . "/../public/views/errors/404.php";
        exit;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        $logger->error("405 Method Not Allowed: /$requestUri");
        echo json_encode(["error" => "Method Not Allowed"]);
        exit;
}
