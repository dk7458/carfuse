<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Load Bootstrap & Services
$bootstrap = require_once __DIR__ . '/../bootstrap.php';
$logger = $bootstrap['logger'];

// ✅ Start Secure Session
startSecureSession();

// ✅ Get Requested URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$logger->info("Requested URI: $requestUri");

// ✅ API REQUEST HANDLING
if (strpos($requestUri, '/api/') === 0) {
    $apiPath = $_SERVER['DOCUMENT_ROOT'] . $requestUri . '.php';

    if (file_exists($apiPath)) {
        $logger->info("API Request Served: $requestUri");
        require $apiPath;
        exit;
    } else {
        http_response_code(404);
        $logger->error("API Not Found: $requestUri");
        echo json_encode(["error" => "API Not Found"]);
        exit;
    }
}

// ✅ FastRoute Dispatching for Views
$dispatcher = require __DIR__ . '/../config/routes.php';
$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $requestUri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        // ✅ Handle Views in Subfolders
        $viewPath = __DIR__ . "/views/" . $routeInfo[1];

        if (file_exists($viewPath)) {
            $logger->info("Rendering View: " . $routeInfo[1]);
            require $viewPath;
        } else {
            http_response_code(404);
            $logger->error("View Not Found: " . $routeInfo[1]);
            require __DIR__ . "/views/errors/404.php";
        }
        exit;

    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        $logger->error("404 Not Found: $requestUri");
        require __DIR__ . "/views/errors/404.php";
        exit;
}
?>
