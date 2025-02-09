<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../app/helpers/SecurityHelper.php';
require_once __DIR__ . '/../../config/api.php';

// Helper function for logging
function logMessage($msg) {
    file_put_contents(__DIR__ . '/../../logs/debug.log', date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Log the API request at the very start, including requested URI
logMessage("API request received for " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

// Optional authentication check flag
$requiresAuth = true;

// Define authentication function with improved error handling
function requireAuth() {
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        logMessage("Authentication failed: Missing Authorization header");
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Unauthorized access: Authorization header missing"]);
        exit();
    }
    logMessage("Authentication succeeded with header: " . $_SERVER['HTTP_AUTHORIZATION']);
}

// Perform authentication if required
if ($requiresAuth) {
    requireAuth();
}

// Confirm FastRoute processing by logging the matched route
// (Assuming FastRoute routing passes control here)
logMessage("FastRoute processed endpoint: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

// Set header always for valid JSON
header("Content-Type: application/json");

if (!isUserLoggedIn()) {
    http_response_code(403);
    echo json_encode(["error" => "Access denied"]);
    exit();
}

// Example response for test endpoint
echo json_encode(["message" => "Test endpoint accessed successfully"]);
logMessage("/test API processed successfully");
?>
