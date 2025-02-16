<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Controllers/AuthController.php';

use App\Controllers\AuthController;

// Set Headers
header('Content-Type: application/json');

// Validate Content-Type header
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (stripos($contentType, 'application/json') === false) {
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// ✅ Retrieve JSON Input
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Initialize AuthController
$authController = new AuthController();
$authController->register($data);
