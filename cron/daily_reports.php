<?php
/**
 * File Path: /cron/daily_reports.php
 * Description: Generates and sends daily reports (e.g., booking stats, revenue) to admins.
 * Changelog:
 * - Added error handling for empty booking stats.
 * - Improved logging format.
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/plain; charset=UTF-8');

function logDailyReportAction($message) {
    echo date('[Y-m-d H:i:s] ') . $message . "\n";
}

try {
    logDailyReportAction("Starting daily reports...");

    // Fetch daily booking stats
    $query = "
        SELECT COUNT(*) AS total_bookings, COALESCE(SUM(total_price), 0) AS total_revenue 
        FROM bookings 
        WHERE DATE(created_at) = CURDATE()
    ";
    $bookingStats = $conn->query($query)->fetch_assoc();

    if (!$bookingStats) {
        throw new Exception("Failed to fetch booking stats for today.");
    }

    $totalBookings = $bookingStats['total_bookings'] ?? 0;
    $totalRevenue = pa
    $bookingStats['total_revenue'] ?? 0;

    // Construct the report
    $report = "Daily Report - " . date('Y-m-d') . ":\n";
    $report .= "Total Bookings: $totalBookings\n";
    $report .= "Total Revenue: $totalRevenue PLN\n";

    // Send report to admins
    foreach (fetchAdminEmails($conn) as $email) {
        if (sendNotification('email', $email, 'Daily Report', $report)) {
            logDailyReportAction("Report sent to $email.");
        } else {
            logDailyReportAction("Failed to send report to $email.");
        }
    }

    logDailyReportAction("Daily reports completed.");
} catch (Exception $e) {
    logError("Daily Report Cron Error: " . $e->getMessage());
    logDailyReportAction("Error: " . $e->getMessage());
}
