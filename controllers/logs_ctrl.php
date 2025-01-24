<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /controllers/logs_ctrl.php
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'functions/global.php';

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'fetch':
            // Fetch logs
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
            break;

        case 'clear_logs':
            // Clear logs older than 30 days
            $threshold = date('Y-m-d', strtotime('-30 days'));
            $stmt = $conn->prepare("DELETE FROM logs WHERE DATE(timestamp) < ?");
            $stmt->bind_param('s', $threshold);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'view':
            // View detailed log
            $logId = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM logs WHERE id = ?");
            $stmt->bind_param('i', $logId);
            $stmt->execute();
            $log = $stmt->get_result()->fetch_assoc();
            echo json_encode(['success' => true, 'log' => $log]);
            break;

        case 'chart_data':
            // Fetch data for chart
            $chartQuery = "SELECT DATE(timestamp) AS log_date, COUNT(*) AS count FROM logs GROUP BY log_date ORDER BY log_date";
            $result = $conn->query($chartQuery);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = ['date' => $row['log_date'], 'count' => $row['count']];
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        default:
            throw new Exception("Unknown action: $action");
    }
} catch (Exception $e) {
    logError("Logs Controller Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
