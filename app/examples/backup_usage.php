<?php

use App\Services\BackupService;

// Create backup service instance
$backupService = new BackupService();

// Create full database backup
try {
    $dbBackupPath = $backupService->createDatabaseBackup(false);
    echo "Database backup created at: $dbBackupPath\n";
} catch (Exception $e) {
    echo "Database backup failed: " . $e->getMessage() . "\n";
}

// Create incremental database backup
try {
    $incrementalBackupPath = $backupService->createDatabaseBackup(true);
    echo "Incremental backup created at: $incrementalBackupPath\n";
} catch (Exception $e) {
    echo "Incremental backup failed: " . $e->getMessage() . "\n";
}

// Create file backup
try {
    $fileBackupPath = $backupService->createFileBackup();
    echo "File backup created at: $fileBackupPath\n";
} catch (Exception $e) {
    echo "File backup failed: " . $e->getMessage() . "\n";
}

// Clean up old backups
try {
    $backupService->cleanupOldBackups();
    echo "Old backups cleaned up successfully\n";
} catch (Exception $e) {
    echo "Cleanup failed: " . $e->getMessage() . "\n";
}
