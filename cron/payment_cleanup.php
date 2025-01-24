<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /cron/payment_cleanup.php
 * Description: Cleans up expired, non-default payment methods.
 * Changelog:
 * - Added deletion of expired payment methods older than 1 year.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'functions/global.php';


header('Content-Type: text/plain; charset=UTF-8');

function logPaymentCleanupAction($message) {
    echo date('[Y-m-d H:i:s] ') . $message . "\n";
}

try {
    logPaymentCleanupAction("Starting payment cleanup...");

    $cleanupQuery = "
        DELETE FROM payment_methods
        WHERE is_default = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ";
    $conn->query($cleanupQuery);

    logPaymentCleanupAction("Expired non-default payment methods cleaned up successfully.");
} catch (Exception $e) {
    logError("Payment Cleanup Error: " . $e->getMessage());
    logPaymentCleanupAction("Error: " . $e->getMessage());
}
