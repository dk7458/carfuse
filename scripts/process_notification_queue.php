$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
?php
/**
 * File Path: /scripts/process_notification_queue.php
 * Description: Processes queued notifications and sends them to the respective recipients.
 * Changelog:
 * - Initial script to process and send queued notifications.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/notifications.php';


echo "Starting notification queue processing...\n";

try {
    // Fetch pending notifications from the queue
    $stmt = $conn->prepare("SELECT * FROM notification_queue WHERE status = 'pending' LIMIT 50");
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($notifications)) {
        echo "No pending notifications to process.\n";
        exit;
    }

    foreach ($notifications as $notification) {
        $notificationId = $notification['id'];
        $userId = $notification['user_id'];
        $type = $notification['type'];
        $message = $notification['message'];

        echo "Processing notification ID: $notificationId...\n";

        // Send notification
        $success = sendNotification($conn, [
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
        ]);

        if ($success) {
            echo "Notification ID: $notificationId sent successfully.\n";

            // Update notification status to 'sent'
            $updateStmt = $conn->prepare("UPDATE notification_queue SET status = 'sent', processed_at = NOW() WHERE id = ?");
            $updateStmt->bind_param('i', $notificationId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            echo "Failed to send notification ID: $notificationId. Retrying later.\n";

            // Optionally increment a retry count or mark as failed after certain attempts
            $updateStmt = $conn->prepare("UPDATE notification_queue SET retry_count = retry_count + 1 WHERE id = ?");
            $updateStmt->bind_param('i', $notificationId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    echo "Notification queue processing completed.\n";
} catch (Exception $e) {
    logError($e->getMessage());
    echo "Error during queue processing: " . $e->getMessage() . "\n";
}
?>
