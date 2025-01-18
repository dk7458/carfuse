<?php
require '../includes/db_connect.php';

// Define the reporting period (e.g., last 30 days)
$startDate = date('Y-m-d', strtotime('-30 days'));
$endDate = date('Y-m-d');

// Fetch revenue data
$revenue = $conn->query("
    SELECT DATE(pickup_date) AS date, SUM(total_price) AS total_revenue 
    FROM bookings 
    WHERE pickup_date BETWEEN '$startDate' AND '$endDate' AND status = 'active'
    GROUP BY DATE(pickup_date)
")->fetch_all(MYSQLI_ASSOC);

// Fetch fleet usage data
$fleetUsage = $conn->query("
    SELECT f.make, f.model, COUNT(b.id) AS usage_count 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.pickup_date BETWEEN '$startDate' AND '$endDate' AND b.status = 'active'
    GROUP BY b.vehicle_id
    ORDER BY usage_count DESC
")->fetch_all(MYSQLI_ASSOC);

// Save report to a file
$reportFile = '../documents/reports/report_' . date('Y_m_d') . '.txt';
file_put_contents($reportFile, "=== Daily Report ===\n\n");

// Add revenue data to the report
file_put_contents($reportFile, "Revenue Data:\n", FILE_APPEND);
foreach ($revenue as $row) {
    file_put_contents($reportFile, "Date: {$row['date']}, Revenue: {$row['total_revenue']} PLN\n", FILE_APPEND);
}

// Add fleet usage data to the report
file_put_contents($reportFile, "\nFleet Usage:\n", FILE_APPEND);
foreach ($fleetUsage as $row) {
    file_put_contents($reportFile, "{$row['make']} {$row['model']}: {$row['usage_count']} times rented\n", FILE_APPEND);
}

echo "Report generated successfully: $reportFile\n";
?>
