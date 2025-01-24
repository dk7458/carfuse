<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /functions/notification.php
 * Purpose: Manages all notification-related operations, including database operations, sending email, SMS, MQTT notifications, and reporting.
 *
 * Changelog:
 * - Merged functionality from /includes/notifications.php into /functions/notification.php.
 * - Added modular support for SMS APIs and MQTT notifications.
 * - Enhanced error handling for notification management.
 */

// Database-related functions

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

/**
 * Generate a notification report.
 * 
 * @param mysqli $conn Database connection.
 * @param string $type Notification type.
 * @param string $startDate Start date for the report.
 * @param string $endDate End date for the report.
 * @return array List of notifications.
 */
function generateNotificationReport($conn, $type, $startDate, $endDate) {
    $query = "SELECT * FROM notifications WHERE type = ? AND created_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $type, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Fetches notifications with optional filters.
 *
 * @param mysqli $conn Database connection.
 * @param string $type Notification type filter.
 * @param string $startDate Start date filter.
 * @param string $endDate End date filter.
 * @param string $search Search term for recipient.
 * @param int $offset Offset for pagination.
 * @param int $limit Number of notifications to fetch.
 * @return mysqli_result Result set of notifications.
 */
function fetchNotifications($conn, $type, $startDate, $endDate, $search, $offset, $limit) {
    $query = "SELECT * FROM notifications WHERE 1=1";
    $params = [];
    $types = '';

    if ($type) {
        $query .= " AND type = ?";
        $params[] = $type;
        $types .= 's';
    }
    if ($startDate) {
        $query .= " AND created_at >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    if ($endDate) {
        $query .= " AND created_at <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    if ($search) {
        $query .= " AND recipient LIKE ?";
        $params[] = "%$search%";
        $types .= 's';
    }

    $query .= " ORDER BY created_at DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Counts notifications with optional filters.
 *
 * @param mysqli $conn Database connection.
 * @param string $type Notification type filter.
 * @param string $startDate Start date filter.
 * @param string $endDate End date filter.
 * @param string $search Search term for recipient.
 * @return int Total number of notifications.
 */
function countNotifications($conn, $type, $startDate, $endDate, $search) {
    $query = "SELECT COUNT(*) as total FROM notifications WHERE 1=1";
    $params = [];
    $types = '';

    if ($type) {
        $query .= " AND type = ?";
        $params[] = $type;
        $types .= 's';
    }
    if ($startDate) {
        $query .= " AND created_at >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    if ($endDate) {
        $query .= " AND created_at <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    if ($search) {
        $query .= " AND recipient LIKE ?";
        $params[] = "%$search%";
        $types .= 's';
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'];
}

// External notification functions

/**
 * Sends an MQTT notification.
 *
 * @param string $topic MQTT topic.
 * @param string $message Notification message.
 */
function sendMQTTNotification($topic, $message) {
    $escapedTopic = escapeshellarg($topic);
    $escapedMessage = escapeshellarg($message);
    $mqttCommand = "mosquitto_pub -t $escapedTopic -m $escapedMessage";
    shell_exec($mqttCommand);
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
    if (!preg_match('/^\+\d{10,15}$/', $phoneNumber)) {
        throw new Exception("Invalid phone number format.");
    }

    $apiKey = getenv('SMS_API_KEY');
    $apiSecret = getenv('SMS_API_SECRET');
    $apiUrl = getenv('SMS_API_URL');

    $postData = [
        'to' => $phoneNumber,
        'message' => $message,
        'from' => $sender ?? 'Carfuse'
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode("$apiKey:$apiSecret"),
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

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

/**
 * Resend a notification.
 * 
 * @param mysqli $conn Database connection.
 * @param array $notification Notification data.
 * @return bool True if the notification is resent successfully.
 */
function resendNotification($conn, $notification) {
    // Placeholder logic to resend the notification
    // This could involve sending an email or SMS again
    return true;
}
?>
