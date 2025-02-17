<?php
// ✅ Standardized API Utilities

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../App/Helpers/SecurityHelper.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ✅ Unified JSON Response Format
function sendJsonResponse($status, $message, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

// ✅ API Error Logging
function logApiError($message) {
    error_log("[API] " . date('Y-m-d H:i:s') . " - {$message}\n", 3, __DIR__ . '/../../logs/debug.log');
}

// ✅ Extract JWT from Headers or Cookies
function getJWT() {
    $headers = getallheaders();
    if (isset($headers['X-Auth-Token']) && preg_match('/Bearer\s+(\S+)/', $headers['X-Auth-Token'], $matches)) {
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
        sendJsonResponse('error', 'Unauthorized: Missing token', [], 401);
    }

    try {
        return (array) JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
    } catch (Exception $e) {
        logApiError("Invalid JWT: " . $e->getMessage());
        sendJsonResponse('error', 'Unauthorized: Invalid token', [], 401);
    }
}
