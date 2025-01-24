<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/api.php';

/**
 * Fetches maintenance logs from the database with optional filters.
 *
 * @param mysqli $conn Database connection.
 * @param array $filters Optional filters for the query.
 * @return array Array of maintenance logs.
 * @throws Exception If a database error occurs.
 */
function fetchMaintenanceLogs($conn, $filters = []) {
    // Base query
    $query = "SELECT id, vehicle_id, description, date, cost FROM maintenance_logs WHERE 1=1";

    // Apply filters
    if (!empty($filters['search'])) {
        $search = $conn->real_escape_string($filters['search']);
        $query .= " AND (description LIKE '%$search%' OR vehicle_id LIKE '%$search%')";
    }
    if (!empty($filters['startDate'])) {
        $startDate = $conn->real_escape_string($filters['startDate']);
        $query .= " AND date >= '$startDate'";
    }
    if (!empty($filters['endDate'])) {
        $endDate = $conn->real_escape_string($filters['endDate']);
        $query .= " AND date <= '$endDate'";
    }

    // Execute query
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }

    // Fetch logs
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    return $logs;
}
?>
