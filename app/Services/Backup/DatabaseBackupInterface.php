<?php

namespace App\Services\Backup;

interface DatabaseBackupInterface
{
    public function buildFullBackupCommand(): string;
    public function buildIncrementalBackupCommand(): string;
    public function validateBackup(string $path): bool;
}
