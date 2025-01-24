<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /functions/user.php
 * Purpose: Handles all user-related operations, including fetching, counting, and logging user activity.
 *
 * Changelog:
 * - Refactored from functions.php to user.php (Date).
 * - Enhanced SQL query validation for user-related operations.
 * - Added robust logging for sensitive actions.
 */

/**
 * Fetch users based on filters.
 * 
 * @param mysqli $conn
 * @param array $filters
 * @return array
 */
function fetchUsers($conn, $filters) {
    $query = "SELECT * FROM users WHERE 1";
    $params = [];
    $types = '';

    if (!empty($filters['search'])) {
        $query .= " AND (name LIKE ? OR surname LIKE ? OR email LIKE ?)";
        $searchParam = '%' . $filters['search'] . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    if (!empty($filters['role'])) {
        $query .= " AND role = ?";
        $params[] = $filters['role'];
        $types .= 's';
    }

    if ($filters['status'] !== '') {
        $query .= " AND active = ?";
        $params[] = $filters['status'];
        $types .= 'i';
    }

    $query .= " LIMIT ?, ?";
    $params[] = $filters['offset'];
    $params[] = $filters['itemsPerPage'];
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

/**
 * Count users based on filters.
 * 
 * @param mysqli $conn
 * @param string $search
 * @param string $role
 * @param string $status
 * @return int
 */
function countUsers($conn, $search = '', $role = '', $status = '') {
    $query = "SELECT COUNT(*) AS total FROM users WHERE 1";
    $params = [];
    $types = '';

    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR surname LIKE ? OR email LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    if (!empty($role)) {
        $query .= " AND role = ?";
        $params[] = $role;
        $types .= 's';
    }

    if ($status !== '') {
        $query .= " AND active = ?";
        $params[] = $status;
        $types .= 'i';
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

/**
 * Log sensitive actions to the database.
 * 
 * @param int $userId
 * @param string $action
 * @param string $details
 */
function logSensitiveAction($userId, $action, $details) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
}
?>
