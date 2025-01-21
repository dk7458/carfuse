<?php
// File Path: /controllers/logs_ctrl.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Handle actions
try {
    $action = $_GET['action'] ?? '';

    // Fetch logs
    if ($action === 'fetch') {
        $logType = $_GET['log_type'] ?? '';
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $query = "SELECT * FROM logs WHERE 1";
        $params = [];
        $types = '';

        if (!empty($logType)) {
            $query .= " AND log_type = ?";
            $params[] = $logType;
            $types .= 's';
        }
        if (!empty($startDate)) {
            $query .= " AND DATE(timestamp) >= ?";
            $params[] = $startDate;
            $types .= 's';
        }
        if (!empty($endDate)) {
            $query .= " AND DATE(timestamp) <= ?";
            $params[] = $endDate;
            $types .= 's';
        }

        $query .= " ORDER BY timestamp DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $logs]);
        exit;
    }

    // Clear logs older than 30 days
    if ($action === 'clear_logs') {
        $threshold = date('Y-m-d', strtotime('-30 days'));
        $stmt = $conn->prepare("DELETE FROM logs WHERE DATE(timestamp) < ?");
        $stmt->bind_param('s', $threshold);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    // View detailed log
    if ($action === 'view') {
        $logId = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM logs WHERE id = ?");
        $stmt->bind_param('i', $logId);
        $stmt->execute();
        $log = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'log' => $log]);
        exit;
    }

    // Fetch data for chart
    if ($action === 'chart_data') {
        $chartQuery = "SELECT DATE(timestamp) AS log_date, COUNT(*) AS count FROM logs GROUP BY log_date ORDER BY log_date";
        $result = $conn->query($chartQuery);
        $dates = [];
        $counts = [];
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['log_date'];
            $counts[] = $row['count'];
        }
        echo json_encode(['dates' => $dates, 'counts' => $counts]);
        exit;
    }

    throw new Exception("Unknown action: $action");
} catch (Exception $e) {
    logError("Error Log Viewer Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
