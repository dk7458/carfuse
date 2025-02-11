<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ✅ Load encryption keys
$config = require __DIR__ . '/encryption.php';
$jwtSecret = $config['jwt_secret'] ?? '';

header('Content-Type: application/json');

// ✅ Extract JWT from Authorization Header or Cookie
function getJWT() {
    $headers = getallheaders();
    if (isset($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        return $matches[1];
    }
    return $_COOKIE['jwt'] ?? null;
}

// ✅ Validate JWT and Decode User Info
function validateToken() {
    global $jwtSecret;

    $jwt = getJWT();
    if (!$jwt) {
        logApiError("Missing JWT");
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized: Missing token"]);
        exit;
    }

    try {
        return (array) JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
    } catch (Exception $e) {
        logApiError("Invalid JWT: " . $e->getMessage());
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized: Invalid token"]);
        exit;
    }
}

// ✅ Log API Errors for Debugging
function logApiError($message) {
    error_log("[API] " . date('Y-m-d H:i:s') . " - {$message}\n", 3, __DIR__ . '/../logs/debug.log');
}

// ✅ CORS Handling (Apply to All Requests)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// ✅ Handle CORS Preflight Requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// ✅ Parse API request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$apiPath = str_replace('/api/', '', parse_url($requestUri, PHP_URL_PATH));

// ✅ Allow Public Access to Login/Register APIs
$publicRoutes = ['auth/login', 'auth/register'];
if (!in_array($apiPath, $publicRoutes)) {
    validateToken();
}

// ✅ Dynamically Route API Calls
$apiFile = __DIR__ . '/' . $apiPath . '.php';
if (file_exists($apiFile)) {
    logApiError("Processing API endpoint: $apiPath");
    require_once $apiFile;
} else {
    logApiError("API Endpoint Not Found: $apiPath");
    http_response_code(404);
    echo json_encode(['error' => 'API not found']);
}
