<?php
// File Path: /controllers/report_ctrl.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf_generator.php';

header('Content-Type: application/json');

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

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

    if ($category === 'bookings') {
        $query = "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM bookings WHERE 1";
    } elseif ($category === 'revenue') {
        $query = "SELECT DATE(created_at) AS date, SUM(total_price) AS total FROM bookings WHERE 1";
    } elseif ($category === 'users') {
        $query = "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM users WHERE 1";
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

// Handle actions
try {
    $action = $_GET['action'] ?? '';
    $category = $_GET['category'] ?? 'bookings';
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;

    if ($action === 'fetch') {
        // Fetch report data
        $data = fetchReportData($conn, $dateFrom, $dateTo, $category);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'export_csv') {
        // Fetch data for export
        $data = fetchReportData($conn, $dateFrom, $dateTo, $category);

        // Prepare CSV file
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="report.csv"');

        $output = fopen('php://output', 'w');
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0])); // Add headers
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        fclose($output);
        exit;
    }

    if ($action === 'export_pdf') {
        // Fetch data for export
        $data = fetchReportData($conn, $dateFrom, $dateTo, $category);

        // Generate PDF
        $htmlContent = generateReportPDFContent($data, $category, $dateFrom, $dateTo);
        generatePDF($htmlContent, 'php://output');

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="report.pdf"');
        exit;
    }

    throw new Exception("Nieznana akcja: $action");
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Generate PDF content for reports.
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
    <h1>Raport: <?= ucfirst($category) ?></h1>
    <p>Okres: <?= htmlspecialchars($dateFrom) ?> - <?= htmlspecialchars($dateTo) ?></p>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <?php foreach (array_keys($data[0] ?? []) as $header): ?>
                    <th><?= htmlspecialchars(ucfirst($header)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= htmlspecialchars($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}
?>
