<?php
require '../includes/db_connect.php';
require '../includes/functions.php';

try {
    $currentDate = date('Y-m-d');

    // Fetch daily statistics
    $newBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE DATE(created_at) = '$currentDate'")->fetch_assoc()['total'];
    $expiredContracts = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE dropoff_date < '$currentDate' AND status = 'canceled'")->fetch_assoc()['total'];
    $activeBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'active'")->fetch_assoc()['total'];

    $message = "
        Dzienny Raport - {$currentDate}\n\n
        - Nowe rezerwacje: $newBookings\n
        - Wygasłe umowy: $expiredContracts\n
        - Aktywne rezerwacje: $activeBookings\n
    ";

    // Notify admins
    $admins = $conn->query("SELECT email FROM users WHERE role = 'admin'");
    while ($admin = $admins->fetch_assoc()) {
        sendEmail($admin['email'], "Dzienny Raport", nl2br($message));
    }

    echo "Daily summary sent to admins.\n";
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "An error occurred while sending the daily summary.\n";
}
?>
