
<?php

namespace App\Services;

class PostgreSQLBackup implements DatabaseBackupInterface
{
    public function buildFullBackupCommand(): string
    {
        return "pg_dumpall";
    }

    public function buildIncrementalBackupCommand(): string
    {
        return "pg_dumpall --incremental";
    }

    public function validateBackup(string $path): bool
    {
        $checksum = md5_file($path);
        return !empty($checksum);
    }
}