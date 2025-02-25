<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use DI\Container;
use App\Controllers\AuthController;

$container = require_once __DIR__ . '/../../../config/dependencies.php';


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

// ✅ Initialize AuthController
$authController = $container->get(AuthController::class);
$authController->login($data);
