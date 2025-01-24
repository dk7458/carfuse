<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /functions/report.php
 * Purpose: Handles report generation and data retrieval for maintenance logs and other reporting features.
 *
 * Changelog:
 * - Refactored from functions.php to report.php (Date).
 * - Enhanced filtering for maintenance log queries.
 */

/**
 * Fetch maintenance logs based on filters.
 * 
 * @param mysqli $conn
 * @param string $search
 * @param string $dateRange
 * @param string $startDate
 * @param string $endDate
 * @param int $offset
 * @param int $limit
 * @return mysqli_result
 */
function fetchMaintenanceLogs($conn, $search = '', $dateRange = '', $startDate = '', $endDate = '', $offset = 0, $limit = 10) {
    $query = "SELECT * FROM maintenance_logs WHERE 1";
    $params = [];
    $types = '';

    if (!empty($search)) {
        $query .= " AND (make LIKE ? OR model LIKE ? OR description LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    if (!empty($dateRange)) {
        if ($dateRange === 'last_week') {
            $query .= " AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        } elseif ($dateRange === 'last_month') {
            $query .= " AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        }
    }

    if (!empty($startDate)) {
        $query .= " AND maintenance_date >= ?";
        $params[] = $startDate;
        $types .= 's';
    }

    if (!empty($endDate)) {
        $query .= " AND maintenance_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    $query .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Count maintenance logs based on filters.
 * 
 * @param mysqli $conn
 * @param string $search
 * @param string $dateRange
 * @param string $startDate
 * @param string $endDate
 * @return int
 */
function countMaintenanceLogs($conn, $search = '', $dateRange = '', $startDate = '', $endDate = '') {
    $query = "SELECT COUNT(*) AS total FROM maintenance_logs WHERE 1";
    $params = [];
    $types = '';

    if (!empty($search)) {
        $query .= " AND (make LIKE ? OR model LIKE ? OR description LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    if (!empty($dateRange)) {
        if ($dateRange === 'last_week') {
            $query .= " AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        } elseif ($dateRange === 'last_month') {
            $query .= " AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        }
    }

    if (!empty($startDate)) {
        $query .= " AND maintenance_date >= ?";
        $params[] = $startDate;
        $types .= 's';
    }

    if (!empty($endDate)) {
        $query .= " AND maintenance_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}
?>
