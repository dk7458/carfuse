$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /includes/report_helpers.php

require_once __DIR__ . '/db_connect.php';

/**
 * Fetch report data based on type, date range, and filters.
 *
 * @param mysqli $conn Database connection
 * @param string $reportType Type of the report (e.g., bookings, payments, registrations, fleet_usage)
 * @param string $startDate Start date for the report range
 * @param string $endDate End date for the report range
 * @return array Report data
 */
function fetchReportData($conn, $reportType, $startDate, $endDate) {
    switch ($reportType) {
        case 'bookings':
            $query = "
                SELECT 
                    id, user_id, vehicle_id, pickup_date, dropoff_date, total_price, status 
                FROM bookings 
                WHERE DATE(created_at) BETWEEN ? AND ? 
                ORDER BY created_at DESC
            ";
            break;

        case 'payments':
            $query = "
                SELECT 
                    id, user_id, method_name, amount, created_at 
                FROM payments 
                WHERE DATE(created_at) BETWEEN ? AND ? 
                ORDER BY created_at DESC
            ";
            break;

        case 'registrations':
            $query = "
                SELECT 
                    id, name, surname, email, created_at 
                FROM users 
                WHERE DATE(created_at) BETWEEN ? AND ? 
                ORDER BY created_at DESC
            ";
            break;

        case 'fleet_usage':
            $query = "
                SELECT 
                    f.id AS vehicle_id, f.make, f.model, COUNT(b.id) AS usage_count 
                FROM fleet f
                LEFT JOIN bookings b ON f.id = b.vehicle_id AND DATE(b.created_at) BETWEEN ? AND ?
                GROUP BY f.id, f.make, f.model
                ORDER BY usage_count DESC
            ";
            break;

        default:
            throw new Exception("Invalid report type: $reportType");
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Export report data as CSV.
 *
 * @param array $data Report data
 * @param string $filename Desired file name for the CSV
 */
function exportAsCsv(array $data, $filename = "report.csv") {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=$filename");

    $output = fopen('php://output', 'w');

    // Add column headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }

    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Summarize data for visualization.
 *
 * @param array $data Report data
 * @param string $type Visualization type (e.g., line, bar, pie)
 * @return array Prepared data for visualization
 */
function prepareVisualizationData(array $data, $type) {
    $result = [];
    
    switch ($type) {
        case 'line':
        case 'bar':
            foreach ($data as $row) {
                $result['labels'][] = $row['month'] ?? $row['created_at'];
                $result['values'][] = $row['total_price'] ?? $row['amount'] ?? $row['usage_count'] ?? 0;
            }
            break;

        case 'pie':
            foreach ($data as $row) {
                $result['labels'][] = $row['make'] ?? $row['status'];
                $result['values'][] = $row['usage_count'] ?? $row['count'] ?? 0;
            }
            break;

        default:
            throw new Exception("Invalid visualization type: $type");
    }

    return $result;
}
?>
