<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Load Bootstrap & Services
$bootstrap = require_once __DIR__ . '/../bootstrap.php';
$loggerClosure = $bootstrap['logger'];

// Add minimal LoggerWrapper to wrap the closure
class LoggerWrapper {
    private $logger;
    public function __construct($logger) {
        if (is_callable($logger)) {
            $this->logger = $logger;
        } else {
            throw new InvalidArgumentException("Logger must be a callable.");
        }
    }
    public function info($message) {
        call_user_func($this->logger, 'info', $message);
    }
    public function error($message) {
        call_user_func($this->logger, 'error', $message);
    }
}
$logger = new LoggerWrapper($loggerClosure);

// ✅ Define public pages that do not require authentication
$publicPages = ['/', '/index.php', '/home', '/auth/login', '/auth/register', '/vehicles'];

// ✅ Get Requested URI & Log Request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$logger->info("Requested URI: $requestUri");

// ✅ Ensure URL is properly formatted (prevent double slashes)
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

// ✅ FastRoute Dispatching for Views
$dispatcher = require __DIR__ . '/../config/routes.php';
if (!is_callable($dispatcher)) {
    throw new Exception("FastRoute dispatcher is not valid.");
}

// Correctly invoke the closure with method and URI
$routeInfo = $dispatcher($_SERVER['REQUEST_METHOD'], "/$requestUri");
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_callable($handler)) {
            $logger->info("Executing handler for route: /$requestUri");
            // Pass the logger to the handler
            $handler($logger, ...$vars);
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
