<?php
// File Path: /includes/notifications.php
require_once __DIR__ . '/email.php';

/**
 * Send a notification to the user via email or SMS.
 *
 * @param string $type 'email' or 'sms'
 * @param string $recipient Recipient's email or phone number.
 * @param string|null $subject Subject of the notification (email only).
 * @param string $message The content of the notification.
 * @return bool True if the notification was sent successfully, false otherwise.
 */
function sendNotification($type, $recipient, $subject = null, $message) {
    if ($type === 'email') {
        if (empty($subject)) {
            error_log("Email subject is required for email notifications.");
            return false;
        }
        return sendEmail($recipient, $subject, $message);
    } elseif ($type === 'sms') {
        return sendSmsNotification($recipient, $message);
    }

    error_log("Invalid notification type: $type");
    return false;
}

/**
 * Send an SMS notification to the user.
 * Integrates with a hypothetical SMS API (future enhancement).
 *
 * @param string $phoneNumber Recipient's phone number.
 * @param string $message The SMS content.
 * @return bool True if the SMS was sent successfully, false otherwise.
 */
function sendSmsNotification($phoneNumber, $message) {
    // Validate phone number
    if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber)) {
        error_log("Invalid phone number format: $phoneNumber");
        return false;
    }

    // Placeholder for SMS gateway integration
    // Example: Twilio API integration or similar
    error_log("SMS notification simulated to $phoneNumber: $message");

    // Log successful SMS simulation
    logAction($_SESSION['user_id'] ?? 0, 'sms_sent', json_encode(['phone' => $phoneNumber, 'message' => $message]));

    return true; // Simulate successful SMS sending
}

/**
 * Log actions performed by the notification system.
 *
 * @param int $userId User ID associated with the action.
 * @param string $action Action performed (e.g., 'email_sent', 'sms_sent').
 * @param string|null $details Additional details about the action.
 */
function logNotificationAction($userId, $action, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>
