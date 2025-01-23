<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /cron/push_notifications.php
 * Description: Processes queued push notifications and sends them to users.
 * Changelog:
 * - Added processing for notifications with type `push`.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


header('Content-Type: text/plain; charset=UTF-8');

function logPushNotificationAction($message) {
    echo date('[Y-m-d H:i:s] ') . $message . "\n";
}

try {
    logPushNotificationAction("Starting push notifications...");

    // Fetch queued push notifications
    $query = "
        SELECT id, user_id, message 
        FROM notifications 
        WHERE type = 'push' AND status = 'pending'
    ";
    $notifications = $conn->query($query);

    while ($notification = $notifications->fetch_assoc()) {
        $notificationId = $notification['id'];
        $userId = $notification['user_id'];
        $message = $notification['message'];

        try {
            // Send the push notification
            if (sendPushNotification($userId, $message)) {
                logPushNotificationAction("Push notification sent successfully to User ID: $userId");

                // Update the notification status
                $stmt = $conn->prepare("UPDATE notifications SET status = 'sent', sent_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $notificationId);
                $stmt->execute();
            } else {
                throw new Exception("Failed to send push notification to User ID: $userId");
            }
        } catch (Exception $e) {
            logError("Push Notification Error: " . $e->getMessage());
            logPushNotificationAction("Error: " . $e->getMessage());
        }
    }

    logPushNotificationAction("Push notifications processing completed.");
} catch (Exception $e) {
    logError("Push Notification Cron Error: " . $e->getMessage());
    logPushNotificationAction("Error: " . $e->getMessage());
}
