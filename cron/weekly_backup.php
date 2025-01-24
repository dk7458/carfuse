<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

/**
 * File Path: /cron/weekly_backup.php
 * Description: Creates a weekly backup of the database and stores it in the backups directory.
 * Changelog:
 * - Added weekly database backup functionality.
 */

require_once BASE_PATH . 'includes/db_connect.php';


header('Content-Type: text/plain; charset=UTF-8');

// Directory for storing backups
$backupDir = __DIR__ . '/../backups/weekly/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Generate backup filename
$backupFile = $backupDir . 'backup_week_' . date('o_W') . '.sql.gz';

try {
    // Command to dump the database
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s %s | gzip > %s',
        escapeshellarg($username),
        escapeshellarg($password),
        escapeshellarg($host),
        escapeshellarg($database),
        escapeshellarg($backupFile)
    );

    // Execute the command
    system($command, $resultCode);

    if ($resultCode === 0) {
        echo date('[Y-m-d H:i:s] ') . "Weekly backup completed successfully: $backupFile\n";
    } else {
        throw new Exception("Failed to create the weekly backup.");
    }
} catch (Exception $e) {
    echo date('[Y-m-d H:i:s] ') . "Error: " . $e->getMessage() . "\n";
}
