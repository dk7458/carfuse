<?php

namespace App\Services\Database;

use App\Database\DatabaseAdapter; // Example reference to your custom DB adapter
use Psr\Log\LoggerInterface;

class DatabaseMaintenanceService
{
    private DatabaseAdapter $dbAdapter;
    private LoggerInterface $logger;

    public function __construct(DatabaseAdapter $dbAdapter, LoggerInterface $logger)
    {
        $this->dbAdapter = $dbAdapter;
        $this->logger = $logger;
    }

    /**
     * Optimize all database tables.
     */
    public function optimizeTables(): bool
    {
        try {
            $result = $this->dbAdapter->optimizeTables();
            $this->logger->info("Optimized all tables successfully.");
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to optimize tables: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete old records from a table based on a date column.
     */
    public function cleanupOldRecords(string $table, string $dateColumn, string $cutoffDate): int
    {
        try {
            $deletedRows = $this->dbAdapter->cleanupOldRecords($table, $dateColumn, $cutoffDate);
            $this->logger->info("Deleted {$deletedRows} rows from {$table}");
            return $deletedRows;
        } catch (\Exception $e) {
            $this->logger->error("Failed to cleanup old records: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Archive data from a source table to an archive table based on a condition.
     */
    public function archiveData(string $sourceTable, string $archiveTable, string $condition): bool
    {
        try {
            $result = $this->dbAdapter->archiveData($sourceTable, $archiveTable, $condition);
            $this->logger->info("Successfully archived data from {$sourceTable} to {$archiveTable}");
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to archive data: " . $e->getMessage());
            throw $e;
        }
    }
}
