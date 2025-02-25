<?php
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';

// Log the receipt of the request
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - Received API /views/dashboard request\n", FILE_APPEND);

// Set header for valid JSON
header("Content-Type: application/json");

// Check if user is logged in
if (!isUserLoggedIn()) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - Unauthorized access to /views/dashboard\n", FILE_APPEND);
    exit();
}

// Explicit success status
http_response_code(200);

// Load dashboard content dynamically
$recentBookings = [
    ["id" => 101, "user" => "John Doe", "car" => "2019 Toyota Camry", "date" => "2023-10-01"],
    ["id" => 102, "user" => "Jane Smith", "car" => "2018 Honda Accord", "date" => "2023-10-02"],
    // ...additional bookings...
];
$stats = [
    "totalUsers" => 1500,
    "totalBookings" => 300,
    // ...additional stats...
];
$notifications = [
    ["id" => 1, "message" => "Your booking has been confirmed."],
    ["id" => 2, "message" => "New car listings available."],
    // ...additional notifications...
];

// Prepare response data
$responseData = [
    "recentBookings" => $recentBookings,
    "stats" => $stats,
    "notifications" => $notifications
];

// Return JSON response with dashboard data
echo json_encode($responseData);

// Log completion of API handling
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - /views/dashboard API processed successfully\n", FILE_APPEND);
?>
