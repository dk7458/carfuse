<?php

use App\Services\CleanupService;
use App\Services\BackupService;

// Initialize services
$backupService = new BackupService();
$cleanupService = new CleanupService($backupService);

// Delete expired payments
try {
    $paymentStats = $cleanupService->deleteExpiredPayments();
    echo "Payment cleanup completed:\n";
    echo "Processed: {$paymentStats['processed']}\n";
    echo "Deleted: {$paymentStats['deleted']}\n";
    echo "Errors: {$paymentStats['errors']}\n";
} catch (Exception $e) {
    echo "Payment cleanup failed: " . $e->getMessage() . "\n";
}

// Clear old logs
try {
    $logStats = $cleanupService->clearOldLogs();
    echo "Log cleanup completed:\n";
    echo "Processed: {$logStats['processed']}\n";
    echo "Deleted: {$logStats['deleted']}\n";
    echo "Errors: {$logStats['errors']}\n";
} catch (Exception $e) {
    echo "Log cleanup failed: " . $e->getMessage() . "\n";
}

// Validate cleanup operations
if ($cleanupService->validateCleanup('payments')) {
    echo "Payment cleanup validation successful\n";
}
