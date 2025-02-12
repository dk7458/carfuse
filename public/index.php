<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Load Bootstrap & Services
$bootstrap = require_once __DIR__ . '/../bootstrap.php';

// ✅ Ensure logger is set
if (!isset($bootstrap['logger']) || !$bootstrap['logger'] instanceof \Psr\Log\LoggerInterface) {
    die("❌ Fatal error: Logger must be an instance of LoggerInterface.");
}
$logger = $bootstrap['logger'];

// ✅ Define public pages that do not require authentication
$publicPages = ['/', '/index.php', '/home', '/auth/login', '/auth/register', '/vehicles'];

// ✅ Get Requested URI & Log Request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$logger->info("Requested URI: $requestUri");

// ✅ Ensure URL is properly formatted (prevent double slashes and trailing slashes)
$requestUri = trim($requestUri, '/');

// ✅ If request is to an API endpoint, route it to public/api.php
if (strpos($requestUri, 'api/') === 0) {
    $_GET['route'] = str_replace('api/', '', $requestUri);
    $logger->info("Routing API Request: $requestUri to api.php");
    require __DIR__ . "/../public/api.php";
    exit;
}

// ✅ Ensure authentication for protected pages
$protectedPages = ['/dashboard', '/profile', '/reports'];
if (in_array('/' . $requestUri, $protectedPages)) {
    startSecureSession();
    if (!isset($_SESSION['user_id'])) {
        header("Location: /auth/login.php");
        exit();
    }
}

// ✅ Load FastRoute Dispatcher
$dispatcher = require __DIR__ . '/../config/routes.php';

// ✅ Ensure dispatcher is valid
if (!$dispatcher instanceof FastRoute\Dispatcher) {
    $logger->error("FastRoute dispatcher is not valid.");
    throw new Exception("FastRoute dispatcher is not valid.");
}

// ✅ Route request
$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], "/$requestUri");

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_callable($handler)) {
            $logger->info("Executing handler for route: /$requestUri");
            $handler(...$vars);
        } else {
            http_response_code(500);
            $logger->error("Handler not callable for route: /$requestUri");
            require __DIR__ . "/../public/views/errors/500.php";
        }
        exit;

    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        $logger->error("404 Not Found: $requestUri");
        require __DIR__ . "/../public/views/errors/404.php";
        exit;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        $logger->error("405 Method Not Allowed: $requestUri");
        echo json_encode(["error" => "Method Not Allowed"]);
        exit;
}
