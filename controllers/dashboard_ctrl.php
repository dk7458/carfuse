<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_GET['action'] === 'get_chart_data') {
    // Fetch data for chart
    $labels = [];
    $bookings = [];
    $revenue = [];

    $result = $conn->query("
        SELECT DATE(created_at) AS date, COUNT(*) AS booking_count, SUM(total_price) AS total_revenue 
        FROM bookings 
        WHERE status = 'paid' 
        GROUP BY DATE(created_at)
    ");

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['date'];
        $bookings[] = (int) $row['booking_count'];
        $revenue[] = (float) $row['total_revenue'];
    }

    echo json_encode([
        'labels' => $labels,
        'bookings' => $bookings,
        'revenue' => $revenue,
    ]);
    exit;
}
?>
