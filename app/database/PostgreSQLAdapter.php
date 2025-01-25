<?php

namespace App\Database;

use PDO;
use PDOException;

class PostgreSQLAdapter implements DatabaseAdapter
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function optimizeTables(): bool
    {
        try {
            $tables = $this->db->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                $this->db->exec("VACUUM (FULL, ANALYZE) $table");
            }
            
            return true;
        } catch (PDOException $e) {
            throw new \Exception("Failed to optimize tables: " . $e->getMessage());
        }
    }

    public function cleanupOldRecords(string $table, string $dateColumn, string $cutoffDate): int
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM $table WHERE $dateColumn < :cutoff");
            $stmt->execute(['cutoff' => $cutoffDate]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \Exception("Failed to cleanup old records: " . $e->getMessage());
        }
    }

    public function archiveData(string $sourceTable, string $archiveTable, string $condition): bool
    {
        try {
            $this->db->beginTransaction();

            $insertQuery = "INSERT INTO $archiveTable SELECT * FROM $sourceTable WHERE $condition";
            $this->db->exec($insertQuery);
            
            $deleteQuery = "DELETE FROM $sourceTable WHERE $condition";
            $this->db->exec($deleteQuery);

            $this->db->commit();
            
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \Exception("Failed to archive data: " . $e->getMessage());
        }
    }
}
