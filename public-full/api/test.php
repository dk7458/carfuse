<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../app/helpers/SecurityHelper.php';
require_once __DIR__ . '/../../config/api.php';

// Log the inclusion of the test API
$logFile = __DIR__ . '/../../../logs/debug.log';
file_put_contents($logFile, "[API] Including test.php" . PHP_EOL, FILE_APPEND);

// Log the API request at the very start, including requested URI
file_put_contents($logFile, "[API] Request received for " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL, FILE_APPEND);

// Ensure response is JSON
header("Content-Type: application/json");

// Optional authentication check flag
$requiresAuth = true;

// Define authentication function with improved error handling
function requireAuth() {
    global $logFile;
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        file_put_contents($logFile, "[API] Authentication failed: Missing Authorization header" . PHP_EOL, FILE_APPEND);
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized access: Authorization header missing"]);
        exit();
    }
    file_put_contents($logFile, "[API] Authentication succeeded with header: " . $headers['Authorization'] . PHP_EOL, FILE_APPEND);
}

// Perform authentication if required
if ($requiresAuth) {
    requireAuth();
}

// Confirm FastRoute processing by logging the matched route
file_put_contents($logFile, "[API] FastRoute processed endpoint: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL, FILE_APPEND);

// Example response for test endpoint
echo json_encode(["message" => "Test endpoint accessed successfully"]);
file_put_contents($logFile, "[API] /test API processed successfully" . PHP_EOL, FILE_APPEND);
?>
