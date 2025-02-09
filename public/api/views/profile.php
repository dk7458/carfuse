<?php
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';

// Log the receipt of the request
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - Received API /views/profile request\n", FILE_APPEND);

// Set header for valid JSON
header("Content-Type: application/json");

// Check if user is logged in
if (!isUserLoggedIn()) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - Unauthorized access to /views/profile\n", FILE_APPEND);
    exit();
}

// Explicit success status
http_response_code(200);

// Load user profile details dynamically
$userProfile = [
    "name" => "John Doe",
    "email" => "john.doe@example.com",
    "accountStatus" => "Active"
    // ...additional profile details...
];

// Return JSON response with user profile details
echo json_encode($userProfile);

// Log completion of API handling
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - /views/profile API processed successfully\n", FILE_APPEND);
?>
