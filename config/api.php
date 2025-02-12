<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ✅ Load encryption keys
$config = require __DIR__ . '/encryption.php';
$jwtSecret = $config['jwt_secret'] ?? '';

header('Content-Type: application/json');

// --- Modified code: Log incoming headers and cookies using X-Auth-Token ---
$tmpHeaders = getallheaders();
if (isset($tmpHeaders['X-Auth-Token'])) {
    // Redact the JWT token value
    $tmpHeaders['X-Auth-Token'] = 'Bearer <redacted>';
}
$tmpCookies = $_COOKIE;
if (isset($tmpCookies['jwt'])) {
    $tmpCookies['jwt'] = '<redacted>';
}
error_log("[API DEBUG] " . date('Y-m-d H:i:s') . " - Headers: " . json_encode($tmpHeaders) . "\n", 3, __DIR__ . '/../logs/debug.log');
error_log("[API DEBUG] " . date('Y-m-d H:i:s') . " - Cookies: " . json_encode($tmpCookies) . "\n", 3, __DIR__ . '/../logs/debug.log');
// --- End modified code ---

// ✅ Extract JWT from X-Auth-Token Header or Cookie
function getJWT() {
    $headers = getallheaders();
    if (isset($headers['X-Auth-Token']) && preg_match('/Bearer\s+(\S+)/', $headers['X-Auth-Token'], $matches)) {
        return trim($matches[1]);
    }
    return isset($_COOKIE['jwt']) ? trim($_COOKIE['jwt']) : null;
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
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');

// ✅ Handle CORS Preflight Requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// ✅ Parse API request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$apiPath = str_replace('/api/', '', parse_url($requestUri, PHP_URL_PATH));

// ✅ Define Public and Protected Routes
$publicRoutes = ['auth/login', 'auth/register', 'home', 'vehicles', 'auth/password_reset'];
$protectedRoutes = ['user/dashboard', 'user/profile', 'user/reports'];

// ✅ Enforce JWT Authentication for Protected Routes
if (in_array($apiPath, $protectedRoutes)) {
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
