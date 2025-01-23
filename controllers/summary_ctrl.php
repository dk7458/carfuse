<?php
// File Path: /controllers/summary_ctrl.php
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

enforceRole(['admin', 'super_admin'],'/public/login.php'); 
function getSummaryData() {
    global $conn;

    // Quick Statistics
    $totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
    $totalFleet = $conn->query("SELECT COUNT(*) FROM fleet")->fetch_row()[0];
    $availableFleet = $conn->query("SELECT COUNT(*) FROM fleet WHERE availability = 1")->fetch_row()[0];

    // Recent Activities
    $recentUsers = $conn->query("SELECT name, email FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    $recentBookings = $conn->query("
        SELECT CONCAT(f.make, ' ', f.model) AS vehicle, CONCAT(u.name, ' ', u.surname) AS user, b.pickup_date
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fleet f ON b.vehicle_id = f.id
        ORDER BY b.created_at DESC LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    return [
        'total_users' => $totalUsers,
        'total_bookings' => $totalBookings,
        'total_fleet' => $totalFleet,
        'available_fleet' => $availableFleet,
        'recent_users' => $recentUsers,
        'recent_bookings' => $recentBookings,
    ];
}
