<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Controllers/AuthController.php';
require_once __DIR__ . '/bootstrap.php'; // Include bootstrap to get container and services

use App\Controllers\AuthController;
use App\Helpers\ApiHelper;
use App\Helpers\ExceptionHandler;

// Get bootstrap return values
$bootstrap = require_once __DIR__ . '/bootstrap.php';
$container = $bootstrap['container'];
$exceptionHandler = $bootstrap['exceptionHandler'];
$logger = $bootstrap['loggers']['api']; // Use api logger specifically for this endpoint

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiHelper::sendJsonResponse('error', 'Method Not Allowed', [], 405);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$authController = new AuthController();

try {
    $result = $authController->register($data);
    ApiHelper::sendJsonResponse('success', 'User registered successfully', ['user_id' => $result->id], 201);
} catch (Exception $e) {
    $exceptionHandler->handleException($e);
}
