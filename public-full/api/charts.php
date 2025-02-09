<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Access-Control-Allow-Origin: *");  // Allow cross-origin requests
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Verify user authentication
if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

// Sample chart data (Replace with real database queries)
$data = [
    "bookingTrends" => [
        "labels" => ["Jan", "Feb", "Mar", "Apr"],
        "datasets" => [
            [
                "label" => "Bookings",
                "data" => [10, 25, 40, 60],
                "backgroundColor" => "rgba(54, 162, 235, 0.5)"
            ]
        ]
    ],
    "revenueTrends" => [
        "labels" => ["Jan", "Feb", "Mar", "Apr"],
        "datasets" => [
            [
                "label" => "Revenue",
                "data" => [1000, 2500, 4000, 6000],
                "backgroundColor" => "rgba(75, 192, 192, 0.5)"
            ]
        ]
    ]
];

// Return JSON response
echo json_encode($data);
exit();
