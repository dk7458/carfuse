<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /cron/database_maintenance.php
 * Description: Performs database maintenance tasks such as optimizing tables and cleaning up old logs.
 * Changelog:
 * - Added table optimization.
 * - Added log cleanup for entries older than 90 days.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'functions/global.php';


header('Content-Type: text/plain; charset=UTF-8');

// Function to log maintenance actions
function logMaintenanceAction($message) {
    echo date('[Y-m-d H:i:s] ') . $message . "\n";
}

try {
    logMaintenanceAction("Starting database maintenance...");

    // Optimize frequently used tables
    $tablesToOptimize = ['bookings', 'users', 'notifications', 'timed_events'];
    foreach ($tablesToOptimize as $table) {
        $conn->query("OPTIMIZE TABLE `$table`");
        logMaintenanceAction("Optimized table: $table");
    }

    // Clean up old logs (older than 90 days)
    $logCleanupQuery = "
        DELETE FROM logs
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ";
    $conn->query($logCleanupQuery);
    logMaintenanceAction("Cleaned up old logs.");

    logMaintenanceAction("Database maintenance completed successfully.");
} catch (Exception $e) {
    logError("Database Maintenance Error: " . $e->getMessage());
    logMaintenanceAction("Error: " . $e->getMessage());
}
