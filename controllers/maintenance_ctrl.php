<?php
require_once BASE_PATH . '/functions/email.php';require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /controllers/maintenance_ctrl.php
 * Description: Manages maintenance logs and reminders for vehicles.
 * Changelog:
 * - Added batch delete functionality for maintenance logs.
 * - Added export functionality for maintenance logs (CSV and PDF).
 * - Added more granular maintenance reminders (e.g., daily check for vehicles with upcoming service needs).
 */

require_once BASE_PATH . 'includes/db_connect.php';
require_once BASE_PATH . 'functions/global.php';
require_once BASE_PATH . 'includes/email.php'; // Include email functions

enforceRole(['admin', 'super_admin'], '/public/login.php'); 

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

        require_once BASE_PATH . 'includes/pdf_generator.php';

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

    // Daily check for vehicles with upcoming service needs
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'daily_check') {
        $query = "
            SELECT f.make, f.model, f.registration_number, ml.description, ml.maintenance_date
            FROM maintenance_logs ml
            JOIN fleet f ON ml.vehicle_id = f.id
            WHERE ml.maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ";
        $result = $conn->query($query);
        $upcomingServices = $result->fetch_all(MYSQLI_ASSOC);

        foreach ($upcomingServices as $service) {
            $message = sprintf(
                "Upcoming maintenance for %s %s (Registration: %s) on %s. Description: %s",
                htmlspecialchars($service['make']),
                htmlspecialchars($service['model']),
                htmlspecialchars($service['registration_number']),
                htmlspecialchars($service['maintenance_date']),
                htmlspecialchars($service['description'])
            );

            // Send notification to admin
            foreach (fetchAdminEmails($conn) as $email) {
                sendEmail($email, 'Upcoming Vehicle Maintenance', $message);
            }
        }

        echo json_encode(['success' => 'Daily check completed.']);
        exit;
    }

    throw new Exception("Invalid action.");
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Fetches email addresses of all admin users.
 *
 * @param mysqli $conn Database connection.
 * @return array Array of admin email addresses.
 */
function fetchAdminEmails($conn) {
    $query = "SELECT email FROM users WHERE role IN ('admin', 'super_admin')";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }

    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }

    return $emails;
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
