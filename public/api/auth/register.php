<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Controllers/AuthController.php';

use App\Controllers\AuthController;

// ✅ Ensure this script is executed within FastRoute
if (php_sapi_name() !== 'cli-server' && !defined('FASTROUTE_EXECUTION')) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid execution context']));
}

// ✅ Retrieve JSON Input
$data = json_decode(file_get_contents("php://input"), true);
file_put_contents(__DIR__ . '/../../../logs/debug.log', "[REGISTER] Raw JSON Input: " . file_get_contents("php://input") . "\n", FILE_APPEND);

// ✅ Initialize AuthController
$authController = new AuthController();

// ✅ Process Registration
$authController->register($data);
