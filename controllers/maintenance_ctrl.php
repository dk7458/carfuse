<?php
// File Path: /controllers/maintenance_ctrl.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    // Batch Delete Maintenance Logs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'batch_delete') {
        $logIds = $_POST['log_ids'] ?? [];
        if (empty($logIds)) {
            throw new Exception("No maintenance log IDs provided.");
        }

        $placeholders = implode(',', array_fill(0, count($logIds), '?'));
        $query = "DELETE FROM maintenance_logs WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(str_repeat('i', count($logIds)), ...$logIds);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Selected logs have been deleted successfully.']);
        } else {
            throw new Exception("Failed to delete selected logs.");
        }
        exit;
    }

    // Export Logs as CSV
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'export_csv') {
        $query = "
            SELECT f.make, f.model, f.registration_number, ml.description, ml.maintenance_date, ml.cost
            FROM maintenance_logs ml
            JOIN fleet f ON ml.vehicle_id = f.id
        ";
        $result = $conn->query($query);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="maintenance_logs.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Make', 'Model', 'Registration Number', 'Description', 'Maintenance Date', 'Cost']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    // Export Logs as PDF
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'export_pdf') {
        $query = "
            SELECT f.make, f.model, f.registration_number, ml.description, ml.maintenance_date, ml.cost
            FROM maintenance_logs ml
            JOIN fleet f ON ml.vehicle_id = f.id
        ";
        $result = $conn->query($query);

        require_once __DIR__ . '/../includes/pdf_generator.php';
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $htmlContent = generatePDFContent($data, 'Maintenance Logs');
        generatePDF($htmlContent, 'php://output');

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="maintenance_logs.pdf"');
        exit;
    }

    throw new Exception("Invalid action.");
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Generate PDF content for maintenance logs.
 *
 * @param array $data
 * @param string $title
 * @return string
 */
function generatePDFContent($data, $title) {
    ob_start();
    ?>
    <h1><?= htmlspecialchars($title) ?></h1>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Make</th>
                <th>Model</th>
                <th>Registration Number</th>
                <th>Description</th>
                <th>Maintenance Date</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['make']) ?></td>
                    <td><?= htmlspecialchars($row['model']) ?></td>
                    <td><?= htmlspecialchars($row['registration_number']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['maintenance_date']) ?></td>
                    <td><?= number_format($row['cost'], 2, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}
?>
