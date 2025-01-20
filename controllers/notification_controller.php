<?php
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';


// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

// Handle sending a notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    $userId = intval($_POST['user_id']);
    $message = htmlspecialchars($_POST['message']);

    // Fetch user details
    $stmt = $conn->prepare("SELECT email, phone, name FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found.']);
        exit;
    }

    // Send email notification
    if (sendEmail($user['email'], "Powiadomienie", $message)) {
        echo json_encode(['success' => 'Email notification sent.']);
    } else {
        echo json_encode(['error' => 'Failed to send email notification.']);
    }

    // Optionally send SMS
    if (!empty($user['phone'])) {
        if (sendSMS($user['phone'], $message)) {
            echo json_encode(['success' => 'SMS notification sent.']);
        } else {
            echo json_encode(['error' => 'Failed to send SMS notification.']);
        }
    }

    exit;
}

// Manage notification preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_preferences') {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE users 
        SET email_notifications = ?, sms_notifications = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("iii", $emailNotifications, $smsNotifications, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Preferences updated successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to update preferences.']);
    }
    exit;
}
?>
