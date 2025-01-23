<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// user_queries.php

require_once __DIR__ . '/db_connect.php';

// Fetch user details by ID
function getUserDetails($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, name, surname, email, phone, address, pesel_or_id, email_notifications, sms_notifications FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fetch user bookings
function getUserBookings($conn, $userId) {
    $stmt = $conn->prepare("SELECT b.id, f.make, f.model, f.registration_number, b.pickup_date, b.dropoff_date, b.total_price, b.status, b.rental_contract_pdf FROM bookings b JOIN fleet f ON b.vehicle_id = f.id WHERE b.user_id = ? ORDER BY b.created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch user payment methods
function getUserPaymentMethods($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, method_name, details, is_default FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC, id ASC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch user payment history
function getUserPaymentHistory($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, payment_method, amount, currency, status, created_at FROM payments WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

// Log user action
function logUserAction($conn, $userId, $action, $details = null) {
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $action, $details);
    return $stmt->execute();
}

// Fetch user notifications
function getUserNotifications($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, type, message, sent_at FROM notifications WHERE user_id = ? ORDER BY sent_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}
?>

