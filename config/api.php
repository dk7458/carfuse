<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load encryption keys
$config = require __DIR__ . '/encryption.php';
$jwtSecret = $config['jwt_secret'] ?? '';

header('Content-Type: application/json');

function validateToken()
{
    global $jwtSecret;

    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized: Missing or invalid token"]);
        exit;
    }

    $token = substr($authHeader, 7);
    try {
        return (array) JWT::decode($token, new Key($jwtSecret, 'HS256'));
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized: Invalid token"]);
        exit;
    }
}

function requireAuth() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$authHeader || !isset($_SESSION['user_id'])) {
        error_log("[API] Unauthorized access attempt\n", 3, __DIR__ . '/../logs/debug.log');
        http_response_code(401);
        exit('Unauthorized');
    }
}

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

// Parse API request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$apiPath = str_replace('/api/', '', $requestUri);

// Protect all API routes (except login/register)
if (!in_array($apiPath, ['auth/login', 'auth/register'])) {
    validateToken();
}

// Route API calls
$endpoint = $_GET['endpoint'] ?? '';
$apiFile = __DIR__ . '/' . $endpoint . '.php';
if (file_exists($apiFile)) {
    error_log("[API] Processing endpoint: $endpoint\n", 3, __DIR__ . '/../logs/debug.log');
    requireAuth();
    require_once $apiFile;
} else {
    error_log("[API] Endpoint not found: $endpoint\n", 3, __DIR__ . '/../logs/debug.log');
    http_response_code(404);
    echo json_encode(['error' => 'API not found']);
}
