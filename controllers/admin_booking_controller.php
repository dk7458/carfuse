<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';


// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $bookingId = intval($_GET['id']);

    // Fetch booking
    $result = $conn->query("SELECT * FROM bookings WHERE id = $bookingId");
    if ($result->num_rows === 0) {
        die("Rezerwacja nie istnieje.");
    }
    $booking = $result->fetch_assoc();

    // Perform the requested action
    if ($action === 'cancel' && $booking['status'] === 'active') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'canceled', canceled_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $bookingId);
        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], 'cancel_booking', "Admin canceled booking ID: $bookingId");
            $_SESSION['success_message'] = "Rezerwacja została anulowana.";
        } else {
            $_SESSION['error_message'] = "Nie udało się anulować rezerwacji.";
        }
    } elseif ($action === 'reactivate' && $booking['status'] === 'canceled') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'active', canceled_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $bookingId);
        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], 'reactivate_booking', "Admin reactivated booking ID: $bookingId");
            $_SESSION['success_message'] = "Rezerwacja została przywrócona.";
        } else {
            $_SESSION['error_message'] = "Nie udało się przywrócić rezerwacji.";
        }
    } else {
        $_SESSION['error_message'] = "Nieprawidłowe działanie.";
    }

    redirect('/views/admin/bookings.php');
}

?>
