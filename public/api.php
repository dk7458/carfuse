<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;



// Load encryption keys
$config = require __DIR__ . '/../config/encryption.php';
$jwtSecret = $config['jwt_secret'] ?? '';

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
