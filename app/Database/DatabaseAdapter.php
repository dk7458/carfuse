<?php

namespace App\Database;

interface DatabaseAdapter
{
    public function optimizeTables(): bool;
    public function cleanupOldRecords(string $table, string $dateColumn, string $cutoffDate): int;
    public function archiveData(string $sourceTable, string $archiveTable, string $condition): bool;
}
