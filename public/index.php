<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/routes.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

startSecureSession();

// ✅ Get requested URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ✅ Log every request
file_put_contents(__DIR__ . '/../logs/debug.log', date('Y-m-d H:i:s') . " - Requested URI: $requestUri\n", FILE_APPEND);

// ✅ API REQUEST HANDLING
if (strpos($requestUri, '/api/') === 0) {
    $apiPath = __DIR__ . $requestUri . '.php';
    if (file_exists($apiPath)) {
        require $apiPath;
        exit;
    }
    http_response_code(404);
    echo json_encode(["error" => "API Not Found"]);
    exit;
}

// ✅ FastRoute Dispatching
$dispatcher = require __DIR__ . '/../config/routes.php';
$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $requestUri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        file_put_contents(__DIR__ . '/../logs/debug.log', date('Y-m-d H:i:s') . " - Route Found: " . $routeInfo[1] . "\n", FILE_APPEND);
        require __DIR__ . "/views/" . $routeInfo[1];
        exit;

    case FastRoute\Dispatcher::NOT_FOUND:
        file_put_contents(__DIR__ . '/../logs/debug.log', date('Y-m-d H:i:s') . " - 404 Not Found: $requestUri\n", FILE_APPEND);
        require __DIR__ . "/views/errors/404.php";
        exit;
}
?>
