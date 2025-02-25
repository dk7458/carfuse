<?php
// Enforce GET requests only:
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit();
}

// Log public request
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - Public API request to /views/home\n", FILE_APPEND);

// Allow public API access without authentication
header("Content-Type: application/json");

// Explicit success status
http_response_code(200);

// Load homepage content dynamically
$welcomeMessage = "Welcome to CarFuse!";
$latestListings = [
    ["id" => 1, "title" => "2019 Toyota Camry", "price" => 24000],
    ["id" => 2, "title" => "2018 Honda Accord", "price" => 22000],
    // ...additional listings...
];

// Prepare response data
$responseData = [
    "welcomeMessage" => $welcomeMessage,
    "latestListings" => $latestListings
];

// Return JSON response with site metadata
echo json_encode($responseData);

// Log completion of API handling
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - /views/home API processed successfully\n", FILE_APPEND);
?>
