<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $notificationId = intval($_GET['id']);

    // Fetch notification details
    $result = $conn->query("SELECT * FROM notifications WHERE id = $notificationId");
    if ($result->num_rows === 0) {
        die("Nie znaleziono powiadomienia.");
    }
    $notification = $result->fetch_assoc();

    if ($action === 'resend') {
        // Resend notification
        $user = $conn->query("SELECT * FROM users WHERE id = {$notification['user_id']}")->fetch_assoc();
        if ($notification['type'] === 'email') {
            sendEmail($user['email'], "Powtórne Powiadomienie", $notification['message']);
        } elseif ($notification['type'] === 'sms') {
            sendSMS($user['phone'], $notification['message']);
        }

        $_SESSION['success_message'] = "Powiadomienie zostało ponownie wysłane.";
        redirect('/views/admin/notifications.php');
    } elseif ($action === 'delete') {
        // Delete notification
        $conn->query("DELETE FROM notifications WHERE id = $notificationId");
        $_SESSION['success_message'] = "Powiadomienie zostało usunięte.";
        redirect('/views/admin/notifications.php');
    }
}
?>
