<?php
// Log the receipt of the request
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - Received API /views/vehicles request\n", FILE_APPEND);

// Set header for valid JSON
header("Content-Type: application/json");

// Explicit success status
http_response_code(200);

// Load available vehicles dynamically
$vehicles = [
    ["id" => 1, "name" => "2019 Toyota Camry", "price_per_day" => 50, "availability" => true],
    ["id" => 2, "name" => "2018 Honda Accord", "price_per_day" => 45, "availability" => true],
    ["id" => 3, "name" => "2020 Ford Mustang", "price_per_day" => 70, "availability" => false],
    // ...additional vehicles...
];

// Return JSON response with available vehicles
echo json_encode($vehicles);

// Log completion of API handling
file_put_contents(__DIR__ . '/../../../logs/debug.log', date('Y-m-d H:i:s') . " - /views/vehicles API processed successfully\n", FILE_APPEND);
?>
