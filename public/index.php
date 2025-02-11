<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Load Bootstrap & Services
$bootstrap = require_once __DIR__ . '/../bootstrap.php';
$logger = $bootstrap['logger'];

// ✅ Define public pages that can be accessed freely.
$publicPages = ['/', '/index.php', '/home', '/auth/login', '/auth/register', '/vehicles'];

// ✅ Get Requested URI & Log Request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$logger->info("Requested URI: $requestUri");

// ✅ Ensure URL is properly formatted (prevent double slashes)
$requestUri = trim($requestUri, '/');

// ✅ If request is to an API endpoint, route it to public/api.php
if (strpos($requestUri, 'api/') === 0) {
    $apiPath = __DIR__ . "/../public/api.php"; // Ensure correct API path

    if (file_exists($apiPath)) {
        $_GET['route'] = str_replace('api/', '', $requestUri); // Pass route to api.php
        $logger->info("Routing API Request: $requestUri to api.php");
        require $apiPath;
        exit;
    } else {
        http_response_code(404);
        $logger->error("API Not Found: $requestUri");
        echo json_encode(["error" => "API Not Found"]);
        exit;
    }
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
$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], "/$requestUri");

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        // ✅ Ensure views from subfolders load correctly
        $viewPath = __DIR__ . "/../public/" . ltrim($routeInfo[1], '/');

        if (file_exists($viewPath)) {
            $logger->info("Rendering View: " . $routeInfo[1]);
            require $viewPath;
        } else {
            http_response_code(404);
            $logger->error("View Not Found: " . $routeInfo[1]);
            require __DIR__ . "/../public/views/errors/404.php";
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
?>
