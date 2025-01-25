<?php

namespace App\Services;

interface DatabaseBackupInterface
{
    public function buildFullBackupCommand(): string;
    public function buildIncrementalBackupCommand(): string;
    public function validateBackup(string $path): bool;
}
