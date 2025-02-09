<?php
// Log the receipt of the request
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Received API /test request\n", FILE_APPEND);

// Optional authentication check flag
$requiresAuth = false;

// Define authentication function
function requireAuth() {
	// Check for 'Authorization' header (adjust condition per requirements)
	if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
		file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Authentication failed\n", FILE_APPEND);
		http_response_code(401);
		header("Content-Type: application/json");
		echo json_encode(["error" => "Unauthorized"]);
		exit();
	}
	file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Authentication succeeded\n", FILE_APPEND);
}

// Perform authentication if required
if ($requiresAuth) {
	requireAuth();
}

// Set header always for valid JSON
header("Content-Type: application/json");

// Explicit success status
http_response_code(200);

// ...existing code...
echo json_encode(["status" => "API working"]);
// ...existing code...

// Log completion of API handling
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - /test API processed successfully\n", FILE_APPEND);
?>
