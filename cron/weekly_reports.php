<?php
/**
 * File Path: /cron/weekly_reports.php
 * Description: Generates and sends weekly reports (e.g., bookings and revenue stats) to admins.
 * Changelog:
 * - Created script for weekly aggregated reports.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


header('Content-Type: text/plain; charset=UTF-8');

function logWeeklyReportAction($message) {
    echo date('[Y-m-d H:i:s] ') . $message . "\n";
}

try {
    logWeeklyReportAction("Starting weekly reports...");

    // Fetch weekly booking stats
    $query = "
        SELECT 
            COUNT(*) AS total_bookings, 
            COALESCE(SUM(total_price), 0) AS total_revenue 
        FROM bookings 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ";
    $bookingStats = $conn->query($query)->fetch_assoc();

    if (!$bookingStats) {
        throw new Exception("Failed to fetch booking stats for the week.");
    }

    $totalBookings = $bookingStats['total_bookings'] ?? 0;
    $totalRevenue = $bookingStats['total_revenue'] ?? 0;

    // Construct the report
    $report = "Weekly Report (" . date('Y-m-d', strtotime('-7 days')) . " to " . date('Y-m-d') . "):\n";
    $report .= "Total Bookings: $totalBookings\n";
    $report .= "Total Revenue: $totalRevenue PLN\n";

    // Send report to admins
    foreach (fetchAdminEmails($conn) as $email) {
        if (sendNotification('email', $email, 'Weekly Report', $report)) {
            logWeeklyReportAction("Report sent to $email.");
        } else {
            logWeeklyReportAction("Failed to send report to $email.");
        }
    }

    logWeeklyReportAction("Weekly reports completed.");
} catch (Exception $e) {
    logError("Weekly Report Cron Error: " . $e->getMessage());
    logWeeklyReportAction("Error: " . $e->getMessage());
}
