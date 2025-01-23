<?php
// File Path: /controllers/notification_ctrl.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session_middleware.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notification_helpers.php';

header('Content-Type: application/json');

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'resend_notification':
                $notificationId = intval($_POST['notification_id']);
                if ($notificationId === 0) {
                    throw new Exception("Nieprawidłowy identyfikator powiadomienia.");
                }

                $notification = fetchNotificationById($conn, $notificationId);
                if (!$notification) {
                    throw new Exception("Nie znaleziono powiadomienia.");
                }

                $success = resendNotification($conn, $notification);
                if ($success) {
                    echo json_encode(['success' => 'Powiadomienie zostało wysłane ponownie.']);
                } else {
                    throw new Exception("Nie udało się wysłać powiadomienia ponownie.");
                }
                break;

            case 'delete_notification':
                $notificationId = intval($_POST['notification_id']);
                if ($notificationId === 0) {
                    throw new Exception("Nieprawidłowy identyfikator powiadomienia.");
                }

                if (deleteNotification($conn, $notificationId)) {
                    echo json_encode(['success' => 'Powiadomienie zostało usunięte.']);
                } else {
                    throw new Exception("Nie udało się usunąć powiadomienia.");
                }
                break;

            case 'generate_report':
                $type = $_POST['type'] ?? '';
                $startDate = $_POST['start_date'] ?? '';
                $endDate = $_POST['end_date'] ?? '';

                $reportData = generateNotificationReport($conn, $type, $startDate, $endDate);
                if ($reportData) {
                    echo json_encode(['success' => 'Raport został wygenerowany.', 'data' => $reportData]);
                } else {
                    throw new Exception("Nie udało się wygenerować raportu.");
                }
                break;

            default:
                throw new Exception("Nieznana akcja.");
        }
    } else {
        throw new Exception("Nieprawidłowa metoda żądania.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
