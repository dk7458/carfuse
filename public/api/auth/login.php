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

// Initialize AuthController from container
$authController = $container->get(AuthController::class);

// Process login - will read input directly from the request body
$authController->login();
