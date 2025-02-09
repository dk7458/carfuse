<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../app/helpers/SecurityHelper.php';
require_once __DIR__ . '/../../config/api.php';
error_log("[API] Index accessed\n", 3, __DIR__ . '/../logs/debug.log');

// Get requested API endpoint
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$apiPath = str_replace('/api/', '', $requestUri);

// Ensure response is JSON
header("Content-Type: application/json");

// Check for valid Authorization header or session
$headers = getallheaders();
if (!isset($headers['Authorization']) && !isset($_SESSION['user_logged_in'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$routes = require __DIR__ . '/../config/routes.php';
$requestedEndpoint = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (array_key_exists($requestedEndpoint, $routes)) {
    $endpointFile = __DIR__ . '/' . $routes[$requestedEndpoint];
    if (file_exists($endpointFile)) {
        require $endpointFile;
    } else {
        http_response_code(404);
        echo json_encode(["error" => "API not found"]);
        error_log("[API] 404 - Endpoint file not found: $requestedEndpoint" . PHP_EOL, 3, __DIR__ . '/../logs/debug.log');
    }
} else {
    http_response_code(404);
    echo json_encode(["error" => "API not found"]);
    error_log("[API] 404 - Endpoint not found in routes: $requestedEndpoint" . PHP_EOL, 3, __DIR__ . '/../logs/debug.log');
}
exit();
