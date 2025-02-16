<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Controllers/AuthController.php';

use App\Controllers\AuthController;

// Set Headers
header('Content-Type: application/json');
echo json_encode(["status" => "ok", "message" => "register.php is accessible"]);
exit;
// Validate Content-Type header
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(400);
    echo json_encode(["error" => "Content-Type must be application/json"]);
    exit;
}

// Read and log raw input for debugging malformed requests
$rawInput = file_get_contents("php://input");
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " " . $rawInput . "\n", FILE_APPEND);

// Retrieve JSON Input and handle decoding errors
$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON input"]);
    exit;
}

// Check for POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// Initialize AuthController and pass validated JSON data
$authController = new AuthController();
$authController->register($data);
