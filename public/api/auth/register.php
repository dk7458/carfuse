<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
use DI\Container;
use App\Controllers\AuthController;

// ✅ Set Headers
header('Content-Type: application/json');

// ✅ Check for POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// ✅ Retrieve JSON Input
$data = json_decode(file_get_contents("php://input"), true);
file_put_contents(__DIR__ . '/../../../logs/debug.log', "[REGISTER] Raw JSON Input: " . file_get_contents("php://input") . "\n", FILE_APPEND);

// ✅ Initialize AuthController
$authController = $container->get(AuthController::class);

// ✅ Process Registration
$authController->register($data);
