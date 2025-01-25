<?php

namespace App\Services\Backup;

class MySQLBackup implements DatabaseBackupInterface
{
    public function buildFullBackupCommand(): string
    {
        return "mysqldump --opt --all-databases";
    }

    public function buildIncrementalBackupCommand(): string
    {
        // Hypothetical example (standard mysqldump doesn’t do purely incremental):
        return "mysqldump --opt --all-databases --incremental";
    }

    public function validateBackup(string $path): bool
    {
        $checksum = md5_file($path);
        return !empty($checksum);
    }
}
