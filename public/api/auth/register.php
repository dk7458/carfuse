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

// ✅ Initialize AuthController
$authController = new AuthController();

// ✅ Process Registration
$authController->register($data);
