<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Access-Control-Allow-Origin: *");  // Allow cross-origin requests
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

define('API_TOKEN_EXPIRATION', 3600);

function refreshTokenIfExpired($token) {
    // ...check if token is expired...
    // ...refresh logic...
    return $token; 
}

// Log request details
error_log("[API] Request: " . $_SERVER['REQUEST_URI'] . PHP_EOL, 3, __DIR__ . "/debug.log");

function requireAuth() {
    if (!isUserLoggedIn()) {
        http_response_code(403);
        echo json_encode(["error" => "Access denied"]);
        error_log("[API] Unauthorized request" . PHP_EOL, 3, __DIR__ . "/debug.log");
        exit();
    }
}

$requestedEndpoint = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Handle request routing
switch ($requestedEndpoint) {
    case 'publicEndpoint':
        echo json_encode(["public" => "Access granted without auth"]);
        break;
    case 'secureEndpoint':
        requireAuth();
        echo json_encode(["secure" => "Access granted with auth"]);
        break;
    default:
        http_response_code(404);
        echo json_encode(["error" => "API not found"]);
        error_log("[API] 404 - Endpoint not found: $requestedEndpoint" . PHP_EOL, 3, __DIR__ . "/debug.log");
        break;
}
exit();