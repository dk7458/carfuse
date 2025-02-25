<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Controllers/AuthController.php';

use App\Controllers\AuthController;
use App\Helpers\ApiHelper;
use App\Helpers\ExceptionHandler;

// Initialize ExceptionHandler with the 'api' logger.
$exceptionHandler = new ExceptionHandler(getLogger('api'));

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
