<?php
// ✅ Standardized API Utilities
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../../App/Helpers/ApiHelper.php';

use App\Helpers\ApiHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ✅ API Error Logging
function logApiError($message) {
    error_log("[API] " . date('Y-m-d H:i:s') . " - {$message}\n", 3, __DIR__ . '/../../logs/debug.log');
}

// ✅ Extract JWT from Headers or Cookies
function getJWT() {
    return ApiHelper::getJWT();
}

// ✅ Validate JWT and Decode User Info
function validateToken() {
    global $jwtSecret;

    $jwt = getJWT();
    if (!$jwt) {
        logApiError("Missing JWT");
        ApiHelper::sendJsonResponse('error', 'Unauthorized: Missing token', [], 401);
    }

    try {
        return (array) JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
    } catch (Exception $e) {
        logApiError("Invalid JWT: " . $e->getMessage());
        ApiHelper::sendJsonResponse('error', 'Unauthorized: Invalid token', [], 401);
    }
}
