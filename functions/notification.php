<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /functions/notification.php
 * Purpose: Manages all notification-related operations, including sending email, SMS, MQTT notifications, and reporting.
 *
 * Changelog:
 * - Refactored from functions.php to notification.php (Date).
 * - Added modular support for SMS APIs and MQTT notifications.
 * - Enhanced error handling for notification management.
 */

/**
 * Sends an MQTT notification.
 *
 * @param string $topic MQTT topic.
 * @param string $message Notification message.
 */
function sendMQTTNotification($topic, $message) {
    // Ensure the topic and message are properly escaped for shell execution
    $escapedTopic = escapeshellarg($topic);
    $escapedMessage = escapeshellarg($message);
    $mqttCommand = "mosquitto_pub -t $escapedTopic -m $escapedMessage";
    shell_exec($mqttCommand);
}

/**
 * Fetch a notification by its ID.
 * 
 * @param mysqli $conn
 * @param int $notificationId
 * @return array|null
 */
function fetchNotificationById($conn, $notificationId) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Resend a notification.
 * 
 * @param mysqli $conn
 * @param array $notification
 * @return bool
 */
function resendNotification($conn, $notification) {
    // Logic to resend the notification
    // This could involve sending an email or SMS again
    return true;
}

/**
 * Delete a notification.
 * 
 * @param mysqli $conn
 * @param int $notificationId
 * @return bool
 */
function deleteNotification($conn, $notificationId) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    return $stmt->execute();
}

/**
 * Generate a notification report.
 * 
 * @param mysqli $conn
 * @param string $type
 * @param string $startDate
 * @param string $endDate
 * @return array
 */
function generateNotificationReport($conn, $type, $startDate, $endDate) {
    $query = "SELECT * FROM notifications WHERE type = ? AND sent_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $type, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Sends an SMS notification to a user.
 *
 * @param string $phoneNumber Recipient's phone number.
 * @param string $message Message body.
 * @param string|null $sender Optional sender name.
 * @return bool True if the message is sent successfully.
 * @throws Exception If validation fails or the API call fails.
 */
function sendSmsNotification($phoneNumber, $message, $sender = null) {
    // Validate phone number format
    if (!preg_match('/^\+\d{10,15}$/', $phoneNumber)) {
        throw new Exception("Invalid phone number format.");
    }

    // Load API credentials from configuration
    $apiKey = getenv('SMS_API_KEY');
    $apiSecret = getenv('SMS_API_SECRET');
    $apiUrl = getenv('SMS_API_URL');

    // Prepare API request data
    $postData = [
        'to' => $phoneNumber,
        'message' => $message,
        'from' => $sender ?? 'Carfuse'
    ];

    // Initialize cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode("$apiKey:$apiSecret"),
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    // Execute API request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Handle API response
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if ($responseData['status'] === 'success') {
            return true;
        } else {
            throw new Exception("SMS API error: " . $responseData['message']);
        }
    } else {
        throw new Exception("Failed to send SMS. HTTP status code: $httpCode");
    }
}
?>
