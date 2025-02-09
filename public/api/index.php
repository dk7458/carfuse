<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';

// Get requested API endpoint
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$apiPath = str_replace('/api/', '', $requestUri);

// Ensure response is JSON
header("Content-Type: application/json");

// Check for valid Authorization header or session
$headers = getallheaders();
if (!isset($headers['Authorization']) && !isset($_SESSION['user_logged_in'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

// Route API calls dynamically
$apiFile = __DIR__ . "/$apiPath.php";
if (file_exists($apiFile)) {
    require $apiFile;
} else {
    http_response_code(404);
    echo json_encode(["error" => "API endpoint not found"]);
    exit();
}
