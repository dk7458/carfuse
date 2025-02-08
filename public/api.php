<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load encryption keys
$config = require __DIR__ . '/../config/encryption.php';
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

// Parse API request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$apiPath = str_replace('/api/', '', $requestUri);

// Protect all API routes (except login/register)
if (!in_array($apiPath, ['auth/login', 'auth/register'])) {
    validateToken();
}

// Route API calls
switch ($apiPath) {
    case "user/data":
        require __DIR__ . "/user/data.php";
        break;
    
    case "admin/reports":
        require __DIR__ . "/admin/reports.php";
        break;

    case "shared/charts":
        require __DIR__ . "/shared/charts.php";
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "API endpoint not found"]);
}
