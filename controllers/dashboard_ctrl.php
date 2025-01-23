<?php
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/session_middleware.php';


// Enforce role-based access for admin and super admin
enforceRole(['admin', 'super_admin'], '/public/login.php');

header('Content-Type: application/json');

try {
    // Validate the action parameter
    if (!isset($_GET['action']) || $_GET['action'] !== 'get_chart_data') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
        exit;
    }

    // Validate the chart type (default to 'bookings' if not specified)
    $type = $_GET['type'] ?? 'bookings';
    if (!in_array($type, ['bookings', 'revenue'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid chart type.']);
        exit;
    }

    // Initialize arrays for chart data
    $labels = [];
    $data = [];

    // Build query based on the requested chart type
    if ($type === 'bookings') {
        $query = "
            SELECT 
                DATE(created_at) AS date, 
                COUNT(*) AS booking_count
            FROM bookings 
            WHERE status = 'paid' 
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ";
    } elseif ($type === 'revenue') {
        $query = "
            SELECT 
                DATE(created_at) AS date, 
                COALESCE(SUM(total_price), 0) AS total_revenue
            FROM bookings 
            WHERE status = 'paid' 
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ";
    }

    // Execute the query
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['date'];
            $data[] = $type === 'bookings' ? (int)$row['booking_count'] : (float)$row['total_revenue'];
        }
    } else {
        throw new Exception("Error fetching chart data: " . $conn->error);
    }

    // Return the data in JSON format
    echo json_encode([
        'labels' => $labels,
        'data' => $data,
    ]);
} catch (Exception $e) {
    // Log the error and return a failure response
    logError($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch data.']);
    exit;
}
