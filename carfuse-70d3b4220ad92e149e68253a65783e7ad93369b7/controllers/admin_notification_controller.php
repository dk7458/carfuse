<?php

require '../includes/db_connect.php';
require '../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

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
