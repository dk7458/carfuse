<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../../config/api.php';

// Log the inclusion of the index API
$logFile = __DIR__ . '/../../logs/debug.log';
file_put_contents($logFile, "[API] Including index.php" . PHP_EOL, FILE_APPEND);

// Log the API request at the very start, including requested URI
file_put_contents($logFile, "[API] Request received for " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL, FILE_APPEND);

// Ensure response is JSON
header("Content-Type: application/json");

// Check for valid Authorization header or session
$headers = getallheaders();
if (!isset($headers['Authorization']) && !isset($_SESSION['user_logged_in'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    file_put_contents($logFile, "[API] Unauthorized access - Missing Authorization header or session" . PHP_EOL, FILE_APPEND);
    exit();
}

$routes = require __DIR__ . '/../../config/routes.php';
$requestedEndpoint = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (array_key_exists($requestedEndpoint, $routes)) {
    $endpointFile = __DIR__ . '/' . $routes[$requestedEndpoint];
    if (file_exists($endpointFile)) {
        require $endpointFile;
    } else {
        http_response_code(404);
        echo json_encode(["error" => "API not found"]);
        file_put_contents($logFile, "[API] 404 - Endpoint file not found: $requestedEndpoint" . PHP_EOL, FILE_APPEND);
    }
} else {
    http_response_code(404);
    echo json_encode(["error" => "API not found"]);
    file_put_contents($logFile, "[API] 404 - Endpoint not found in routes: $requestedEndpoint" . PHP_EOL, FILE_APPEND);
}
exit();
