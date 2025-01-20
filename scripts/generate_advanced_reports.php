<?php
require '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';

// Define the reporting range (last 30 days)
$startDate = date('Y-m-d', strtotime('-30 days'));
$endDate = date('Y-m-d');

// Fetch data for analytics
$revenue = $conn->query("SELECT SUM(total_price) AS total FROM bookings WHERE pickup_date BETWEEN '$startDate' AND '$endDate'")->fetch_assoc()['total'];
$topVehicles = $conn->query("
    SELECT f.make, f.model, COUNT(b.id) AS usage 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.pickup_date BETWEEN '$startDate' AND '$endDate' 
    GROUP BY b.vehicle_id 
    ORDER BY usage DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Save report
$reportFile = "../documents/reports/advanced_report_" . date('Y_m_d') . ".txt";
file_put_contents($reportFile, "=== Advanced Monthly Report ===\n\n");
file_put_contents($reportFile, "Revenue: $revenue PLN\n", FILE_APPEND);
file_put_contents($reportFile, "\nTop Vehicles:\n", FILE_APPEND);
foreach ($topVehicles as $vehicle) {
    file_put_contents($reportFile, "{$vehicle['make']} {$vehicle['model']}: {$vehicle['usage']} bookings\n", FILE_APPEND);
}

echo "Advanced report generated: $reportFile\n";
?>
