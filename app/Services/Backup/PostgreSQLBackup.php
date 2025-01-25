<?php

namespace App\Services\Backup;

class PostgreSQLBackup implements DatabaseBackupInterface
{
    public function buildFullBackupCommand(): string
    {
        return "pg_dumpall";
    }

    public function buildIncrementalBackupCommand(): string
    {
        // Hypothetical example for incremental:
        return "pg_dumpall --incremental";
    }

    public function validateBackup(string $path): bool
    {
        $checksum = md5_file($path);
        return !empty($checksum);
    }
}
