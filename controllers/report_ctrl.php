<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /controllers/report_ctrl.php
 * Description: Handles advanced reporting features, including data fetching, CSV and PDF export.
 * Changelog:
 * - Improved error handling and parameter validation.
 * - Added support for weekly reports and detailed data categories.
 * - Refactored PDF generation for dynamic content.
 */

require_once BASE_PATH . 'includes/db_connect.php';
require_once BASE_PATH . 'includes/functions.php';
require_once BASE_PATH . 'includes/pdf_generator.php';
require_once BASE_PATH . 'includes/export_helpers.php';

// Enforce admin or super admin access
enforceRole(['admin', 'super_admin'], '/public/login.php');

header('Content-Type: application/json');

/**
 * Fetch report data based on filters.
 *
 * @param mysqli $conn
 * @param string|null $dateFrom
 * @param string|null $dateTo
 * @param string $category
 * @return array
 */
function fetchReportData($conn, $dateFrom = null, $dateTo = null, $category = 'bookings') {
    $query = "";
    $params = [];
    $types = "";

    switch ($category) {
        case 'bookings':
            $query = "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM bookings WHERE 1";
            break;
        case 'revenue':
            $query = "SELECT DATE(created_at) AS date, SUM(total_price) AS total FROM bookings WHERE 1";
            break;
        case 'users':
            $query = "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM users WHERE 1";
            break;
        default:
            throw new Exception("Invalid report category: $category");
    }

    if (!empty($dateFrom)) {
        $query .= " AND created_at >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }

    if (!empty($dateTo)) {
        $query .= " AND created_at <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }

    $query .= " GROUP BY DATE(created_at) ORDER BY DATE(created_at)";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Fetch comparative weekly report data.
 *
 * @param mysqli $conn
 * @param string $category
 * @return array
 */
function fetchComparativeWeeklyReportData($conn, $category = 'bookings') {
    $query = "";
    switch ($category) {
        case 'bookings':
            $query = "SELECT WEEK(created_at) AS week, COUNT(*) AS count FROM bookings GROUP BY WEEK(created_at)";
            break;
        case 'revenue':
            $query = "SELECT WEEK(created_at) AS week, SUM(total_price) AS total FROM bookings GROUP BY WEEK(created_at)";
            break;
        case 'users':
            $query = "SELECT WEEK(created_at) AS week, COUNT(*) AS count FROM users GROUP BY WEEK(created_at)";
            break;
        default:
            throw new Exception("Invalid report category: $category");
    }

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle actions
try {
    $action = $_GET['action'] ?? '';
    $category = $_GET['category'] ?? 'bookings';
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;

    // Validate date range
    if ($dateFrom && $dateTo && strtotime($dateFrom) > strtotime($dateTo)) {
        throw new Exception("Invalid date range: 'from' date must be earlier than 'to' date.");
    }

    if ($action === 'fetch') {
        // Fetch report data
        $data = fetchReportData($conn, $dateFrom, $dateTo, $category);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'export_csv') {
        // Fetch data for export
        $data = fetchReportData($conn, $dateFrom, $dateTo, $category);

        // Export as CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="report.csv"');

        exportToCSV($data);
        exit;
    }

    if ($action === 'export_pdf') {
        // Fetch data for export
        $data = fetchReportData($conn, $dateFrom, $dateTo, $category);

        // Generate and export PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="report.pdf"');

        $htmlContent = generateReportPDFContent($data, $category, $dateFrom, $dateTo);
        generatePDF($htmlContent, 'php://output');
        exit;
    }

    if ($action === 'fetch_comparative_weekly') {
        // Fetch comparative weekly report data
        $data = fetchComparativeWeeklyReportData($conn, $category);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    throw new Exception("Unknown action: $action");
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Generate HTML content for PDF reports.
 *
 * @param array $data
 * @param string $category
 * @param string|null $dateFrom
 * @param string|null $dateTo
 * @return string
 */
function generateReportPDFContent($data, $category, $dateFrom, $dateTo) {
    ob_start();
    ?>
    <h1>Report: <?= ucfirst($category) ?></h1>
    <p>Period: <?= htmlspecialchars($dateFrom ?? 'Start') ?> - <?= htmlspecialchars($dateTo ?? 'End') ?></p>
    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <?php foreach (array_keys($data[0] ?? ['No Data' => '']) as $header): ?>
                    <th><?= htmlspecialchars(ucfirst($header)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?= htmlspecialchars($cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= count($data[0] ?? ['No Data']) ?>" class="text-center">No data available for the selected period.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}
?>
