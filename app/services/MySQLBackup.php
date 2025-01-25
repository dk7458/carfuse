<?php

namespace App\Services;

class MySQLBackup implements DatabaseBackupInterface
{
    public function buildFullBackupCommand(): string
    {
        return "mysqldump --opt --all-databases";
    }

    public function buildIncrementalBackupCommand(): string
    {
        return "mysqldump --opt --all-databases --incremental";
    }

    public function validateBackup(string $path): bool
    {
        $checksum = md5_file($path);
        return !empty($checksum);
    }
}
