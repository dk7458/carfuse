<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
use App\Controllers\AuthController;

// Get DI container
$container = require_once __DIR__ . '/../../../config/dependencies.php';

// Set Headers
header('Content-Type: application/json');

// Check for POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// Retrieve JSON Input
$input = file_get_contents("php://input");
// Log raw input for debugging if needed
file_put_contents(__DIR__ . '/../../../logs/debug.log', "[REGISTER] Raw JSON Input: " . $input . "\n", FILE_APPEND);

// Initialize AuthController from container
$authController = $container->get(AuthController::class);

// Process Registration - will return JSON response
$authController->register();
