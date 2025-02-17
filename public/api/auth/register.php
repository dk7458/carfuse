<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Controllers/AuthController.php';

use App\Controllers\AuthController;
use PDOException;

// ✅ Set Headers
header('Content-Type: application/json');

// ✅ Check for POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Method Not Allowed', [], 405);
    exit;
}

// ✅ Retrieve JSON Input
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Initialize AuthController
$authController = new AuthController();

try {
    $result = $authController->register($data);
    sendJsonResponse('success', 'User registered successfully', ['user_id' => $result->id], 201);
} catch (PDOException $e) {
    logApiError("Database Error: " . $e->getMessage());

    // ✅ Check for Duplicate Entry using SQLSTATE Code
    if ($e->getCode() == 23000) {
        sendJsonResponse('error', 'User already exists', [], 400);
    }

    sendJsonResponse('error', 'Internal Server Error', [], 500);
} catch (Exception $e) {
    logApiError("Application Error: " . $e->getMessage());
    sendJsonResponse('error', 'Internal Server Error', [], 500);
}
