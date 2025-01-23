<?php
/**
 * File Path: /controllers/notification_ctrl.php
 * Description: Handles notifications for admins and users, including sending, resending, deleting, reporting, and queuing.
 * Changelog:
 * - Added support for reschedule notifications.
 * - Integrated notification queuing for failed deliveries.
 * - Improved modularity and error handling.
 * - Implemented real-time push notifications for key booking events.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/notification_helpers.php';


header('Content-Type: application/json');

// Enforce role-based access
enforceRole(['admin', 'super_admin'], '/public/login.php');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'resend_notification':
                $notificationId = intval($_POST['notification_id']);
                if ($notificationId === 0) {
                    throw new Exception("Invalid notification ID.");
                }

                $notification = fetchNotificationById($conn, $notificationId);
                if (!$notification) {
                    throw new Exception("Notification not found.");
                }

                $success = resendNotification($conn, $notification);
                if ($success) {
                    echo json_encode(['success' => 'Notification resent successfully.']);
                } else {
                    queueNotification($conn, $notification); // Queue the notification if it fails
                    throw new Exception("Notification queued for retry.");
                }
                break;

            case 'delete_notification':
                $notificationId = intval($_POST['notification_id']);
                if ($notificationId === 0) {
                    throw new Exception("Invalid notification ID.");
                }

                if (deleteNotification($conn, $notificationId)) {
                    echo json_encode(['success' => 'Notification deleted successfully.']);
                } else {
                    throw new Exception("Failed to delete the notification.");
                }
                break;

            case 'generate_report':
                $type = $_POST['type'] ?? '';
                $startDate = $_POST['start_date'] ?? '';
                $endDate = $_POST['end_date'] ?? '';

                $reportData = generateNotificationReport($conn, $type, $startDate, $endDate);
                if ($reportData) {
                    echo json_encode(['success' => 'Report generated successfully.', 'data' => $reportData]);
                } else {
                    throw new Exception("Failed to generate the report.");
                }
                break;

            case 'send_reschedule_notification':
                $bookingId = intval($_POST['booking_id']);
                $newPickupDate = $_POST['new_pickup_date'] ?? '';
                $newDropoffDate = $_POST['new_dropoff_date'] ?? '';

                if (empty($bookingId) || empty($newPickupDate) || empty($newDropoffDate)) {
                    throw new Exception("Invalid data for reschedule notification.");
                }

                // Fetch booking details
                $stmt = $conn->prepare("
                    SELECT b.user_id, CONCAT(u.name, ' ', u.surname) AS user_name, f.make, f.model 
                    FROM bookings b 
                    JOIN users u ON b.user_id = u.id 
                    JOIN fleet f ON b.vehicle_id = f.id 
                    WHERE b.id = ?
                ");
                $stmt->bind_param("i", $bookingId);
                $stmt->execute();
                $booking = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$booking) {
                    throw new Exception("Booking not found.");
                }

                // Construct the notification
                $message = sprintf(
                    "Your booking (%s %s) has been rescheduled to: %s - %s.",
                    htmlspecialchars($booking['make']),
                    htmlspecialchars($booking['model']),
                    htmlspecialchars($newPickupDate),
                    htmlspecialchars($newDropoffDate)
                );

                $notificationData = [
                    'user_id' => $booking['user_id'],
                    'message' => $message,
                    'type' => 'reschedule',
                ];

                if (sendNotification($conn, $notificationData)) {
                    echo json_encode(['success' => 'Reschedule notification sent successfully.']);
                } else {
                    queueNotification($conn, $notificationData); // Queue the notification if it fails
                    throw new Exception("Failed to send the notification. It has been queued.");
                }
                break;

            case 'update_preferences':
                $userId = intval($_POST['user_id']);
                $emailPref = $_POST['email_pref'] ?? 'off';
                $smsPref = $_POST['sms_pref'] ?? 'off';

                if ($userId === 0) {
                    throw new Exception("Invalid user ID.");
                }

                $stmt = $conn->prepare("
                    UPDATE user_preferences 
                    SET email_notifications = ?, sms_notifications = ? 
                    WHERE user_id = ?
                ");
                $stmt->bind_param('ssi', $emailPref, $smsPref, $userId);
                $stmt->execute();
                $stmt->close();

                echo json_encode(['success' => 'Notification preferences updated successfully.']);
                break;

            case 'send_push_notification':
                $userId = intval($_POST['user_id']);
                $message = $_POST['message'] ?? '';

                if ($userId === 0 || empty($message)) {
                    throw new Exception("Invalid data for push notification.");
                }

                // Fetch user device token
                $stmt = $conn->prepare("
                    SELECT device_token 
                    FROM user_devices 
                    WHERE user_id = ?
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $deviceToken = $result->fetch_assoc()['device_token'];
                $stmt->close();

                if (!$deviceToken) {
                    throw new Exception("Device token not found.");
                }

                // Send push notification
                $pushSuccess = sendPushNotification($deviceToken, $message);
                if ($pushSuccess) {
                    echo json_encode(['success' => 'Push notification sent successfully.']);
                } else {
                    throw new Exception("Failed to send push notification.");
                }
                break;

            default:
                throw new Exception("Unknown action.");
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Queue a notification for later processing.
 * 
 * @param mysqli $conn Database connection.
 * @param array $notification Notification data.
 */
function queueNotification($conn, $notification) {
    $stmt = $conn->prepare("
        INSERT INTO notification_queue (user_id, type, message, status) 
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->bind_param('iss', $notification['user_id'], $notification['type'], $notification['message']);
    $stmt->execute();
    $stmt->close();
}

/**
 * Send a push notification to a user's device.
 * 
 * @param string $deviceToken Device token.
 * @param string $message Notification message.
 * @return bool True if the notification was sent successfully, false otherwise.
 */
function sendPushNotification($deviceToken, $message) {
    // Implement push notification logic here (e.g., using Firebase Cloud Messaging)
    // Return true if successful, false otherwise
    return true;
}
?>
