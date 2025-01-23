<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch current preferences
$result = $conn->query("SELECT email_notifications, sms_notifications FROM users WHERE id = $userId");
$preferences = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, sms_notifications = ? WHERE id = ?");
    $stmt->bind_param("iii", $emailNotifications, $smsNotifications, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Preferencje powiadomień zostały zaktualizowane.']);
    } else {
        echo json_encode(['error' => 'Wystąpił błąd podczas zapisywania preferencji.']);
    }
    exit();
}
?>
