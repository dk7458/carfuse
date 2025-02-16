<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
use App\Controllers\AuthController;

// Set Headers
header('Content-Type: application/json');

// Validate Content-Type header
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(400);
    echo json_encode(["error" => "Content-Type must be application/json"]);
    exit;
}

// Check for POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// Retrieve and decode JSON input, handling errors
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON input"]);
    exit;
}

// Initialize AuthController and pass validated JSON data
$authController = new AuthController();
$authController->login($data);
