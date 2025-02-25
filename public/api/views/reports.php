<?php
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';

// Log the receipt of the request
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - Received API /views/reports request\n", FILE_APPEND);

// Set header for valid JSON
header("Content-Type: application/json");

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - Unauthorized access to /views/reports\n", FILE_APPEND);
    exit();
}

// Explicit success status
http_response_code(200);

// Load reports data dynamically
$revenueData = [
    "totalRevenue" => 50000,
    "monthlyRevenue" => 5000,
    // ...additional revenue data...
];
$recentTransactions = [
    ["id" => 201, "user" => "John Doe", "amount" => 24000, "date" => "2023-10-01"],
    ["id" => 202, "user" => "Jane Smith", "amount" => 22000, "date" => "2023-10-02"],
    // ...additional transactions...
];
$systemActivity = [
    ["id" => 1, "activity" => "User login", "timestamp" => "2023-10-01 12:00:00"],
    ["id" => 2, "activity" => "Booking created", "timestamp" => "2023-10-01 12:05:00"],
    // ...additional activities...
];

// Prepare response data
$responseData = [
    "revenueData" => $revenueData,
    "recentTransactions" => $recentTransactions,
    "systemActivity" => $systemActivity
];

// Return JSON response with reports data
echo json_encode($responseData);

// Log completion of API handling
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - /views/reports API processed successfully\n", FILE_APPEND);
?>
