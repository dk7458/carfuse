<?php
/**
 * File Path: /includes/notifications.php
 * Description: Helper functions to manage notifications.
 * Changelog:
 * - Added `sendNotification` function to insert notifications.
 * - Added `fetchNotificationById` function to retrieve specific notifications.
 * - Added `fetchNotificationsByUser` function to fetch notifications for a user.
 * - Added `deleteNotification` function to remove a notification.
 */

/**
 * Sends a notification to a user.
 *
 * @param mysqli $conn Database connection.
 * @param array $data Notification data (user_id, message, type).
 * @return bool True if the notification is sent successfully, false otherwise.
 */
function sendNotification($conn, $data) {
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, message, type, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iss", $data['user_id'], $data['message'], $data['type']);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Fetches a notification by ID.
 *
 * @param mysqli $conn Database connection.
 * @param int $id Notification ID.
 * @return array|null Notification data or null if not found.
 */
function fetchNotificationById($conn, $id) {
    $stmt = $conn->prepare("
        SELECT id, user_id, message, type, created_at 
        FROM notifications 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

/**
 * Fetches notifications for a specific user.
 *
 * @param mysqli $conn Database connection.
 * @param int $userId User ID.
 * @param int $limit Number of notifications to fetch.
 * @return array List of notifications.
 */
function fetchNotificationsByUser($conn, $userId, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT id, message, type, created_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

/**
 * Deletes a notification by ID.
 *
 * @param mysqli $conn Database connection.
 * @param int $id Notification ID.
 * @return bool True if the notification is deleted successfully, false otherwise.
 */
function deleteNotification($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>
