<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Controllers/AuthController.php';

use App\Controllers\AuthController;
use App\Helpers\ApiHelper;  // <-- added

// ✅ Set Headers
header('Content-Type: application/json');

// ✅ Check for POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiHelper::sendJsonResponse('error', 'Method Not Allowed', [], 405);
    exit;
}

// ✅ Retrieve JSON Input
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Initialize AuthController
$authController = new AuthController();

try {
    $result = $authController->register($data);
    ApiHelper::sendJsonResponse('success', 'User registered successfully', ['user_id' => $result->id], 201);
} catch (\PDOException $e) {  // ✅ FIX: No need to import PDOException, use \PDOException directly
    logApiError("Database Error: " . $e->getMessage());

    // ✅ Check for Duplicate Entry using SQLSTATE Code
    if ($e->getCode() == "23000") {
        ApiHelper::sendJsonResponse('error', 'User already exists', [], 400);
    }

    ApiHelper::sendJsonResponse('error', 'Internal Server Error', [], 500);
} catch (\Exception $e) {
    logApiError("Application Error: " . $e->getMessage());
    ApiHelper::sendJsonResponse('error', 'Internal Server Error', [], 500);
}
