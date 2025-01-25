<?php

namespace App\Services;

use App\Database\DatabaseAdapter;
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
     * Optimize all database tables
     * @return bool
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
     * Delete old records from a table based on date column
     * @return int Number of deleted rows
     */
    public function cleanupOldRecords(string $table, string $dateColumn, string $cutoffDate): int
    {
        try {
            $deletedRows = $this->dbAdapter->cleanupOldRecords($table, $dateColumn, $cutoffDate);
            $this->logger->info("Deleted $deletedRows rows from $table");
            return $deletedRows;
        } catch (\Exception $e) {
            $this->logger->error("Failed to cleanup old records: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Archive data from source table to archive table
     * @return bool
     */
    public function archiveData(string $sourceTable, string $archiveTable, string $condition): bool
    {
        try {
            $result = $this->dbAdapter->archiveData($sourceTable, $archiveTable, $condition);
            $this->logger->info("Successfully archived data from $sourceTable to $archiveTable");
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to archive data: " . $e->getMessage());
            throw $e;
        }
    }
}
