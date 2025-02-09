<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../app/helpers/SecurityHelper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

define('API_TOKEN_EXPIRATION', 3600);

function refreshTokenIfExpired($token) {
    // ...check if token is expired...
    // ...refresh logic...
    return $token; 
}

$logFile = __DIR__ . '/../logs/debug.log';

// Log request details
error_log("[API] Request: " . $_SERVER['REQUEST_URI'] . PHP_EOL, 3, $logFile);

function requireAuth() {
    global $logFile;
    if (!isUserLoggedIn()) {
        http_response_code(403);
        echo json_encode(["error" => "Access denied"]);
        error_log("[API] Unauthorized request" . PHP_EOL, 3, $logFile);
        exit();
    }
}

$requestedEndpoint = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$endpointFile = __DIR__ . '/../public/api/' . $requestedEndpoint . '.php';

if (file_exists($endpointFile)) {
    // Check if the endpoint requires authentication
    $protectedEndpoints = ['secureEndpoint']; // Add more protected endpoints as needed
    if (in_array($requestedEndpoint, $protectedEndpoints)) {
        requireAuth();
    }
    require $endpointFile;
} else {
    http_response_code(404);
    echo json_encode(["error" => "API not found"]);
    error_log("[API] 404 - Endpoint not found: $requestedEndpoint" . PHP_EOL, 3, $logFile);
}
exit();