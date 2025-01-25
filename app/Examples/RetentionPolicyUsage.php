<?php

use App\Services\RetentionPolicyService;
use App\Services\BackupService;

// Initialize services
$backupService = new BackupService();
$retentionService = new RetentionPolicyService($backupService);

// Enforce log retention policy
try {
    $logStats = $retentionService->enforceLogRetention();
    echo "Log retention enforcement completed:\n";
    echo "Scanned: {$logStats['scanned']}\n";
    echo "Deleted: {$logStats['deleted']}\n";
    echo "Protected: {$logStats['protected']}\n";
} catch (Exception $e) {
    echo "Log retention enforcement failed: " . $e->getMessage() . "\n";
}

// Enforce backup retention policy
try {
    $backupStats = $retentionService->enforceBackupRetention();
    echo "Backup retention enforcement completed:\n";
    echo "Scanned: {$backupStats['scanned']}\n";
    echo "Deleted: {$backupStats['deleted']}\n";
    echo "Protected: {$backupStats['protected']}\n";
} catch (Exception $e) {
    echo "Backup retention enforcement failed: " . $e->getMessage() . "\n";
}

// Enforce payment retention policy
try {
    $paymentStats = $retentionService->enforcePaymentRetention();
    echo "Payment retention enforcement completed:\n";
    echo "Scanned: {$paymentStats['scanned']}\n";
    echo "Deleted: {$paymentStats['deleted']}\n";
    echo "Protected: {$paymentStats['protected']}\n";
} catch (Exception $e) {
    echo "Payment retention enforcement failed: " . $e->getMessage() . "\n";
}
