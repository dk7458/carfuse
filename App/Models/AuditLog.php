<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Helpers\LogQueryBuilder;
use App\Helpers\SecurityHelper;
use DateTime;

/**
 * AuditLog Model
 *
 * Represents the audit_logs table and provides methods for
 * retrieving, searching, and exporting audit data.
 */
class AuditLog
{
    protected $table = 'audit_logs';
    
    /**
     * Get logs with filtering and pagination
     *
     * @param array $filters Filter criteria
     * @return array Logs and pagination information
     */
    public function getLogs(array $filters = []): array
    {
        // Use LogQueryBuilder for building the query
        $query = LogQueryBuilder::buildSelectQuery($filters);
        
        // Execute count query for pagination
        $totalItems = 0;
        $totalPages = 0;
        
        if (!($filters['skip_pagination'] ?? false)) {
            $countResult = DatabaseHelper::select(
                $query['countSql'],
                $query['params'],
                true
            );
            $totalItems = $countResult[0]['total'] ?? 0;
            $perPage = $query['perPage'];
            $totalPages = ceil($totalItems / $perPage);
        }
        
        // Execute the main query
        $logs = DatabaseHelper::select(
            $query['mainSql'],
            $query['params'],
            true
        );
        
        // Process results
        foreach ($logs as &$log) {
            $this->processLogData($log);
        }
        
        // Build response
        $result = ['data' => $logs];
        
        // Add pagination if needed
        if (!($filters['skip_pagination'] ?? false)) {
            $result['pagination'] = [
                'total' => $totalItems,
                'per_page' => $query['perPage'],
                'current_page' => $query['page'],
                'last_page' => $totalPages,
                'from' => (($query['page'] - 1) * $query['perPage']) + 1,
                'to' => min(($query['page'] * $query['perPage']), $totalItems),
            ];
        }
        
        return $result;
    }
    
    /**
     * Get a single log by ID
     *
     * @param int $id Log ID
     * @return array|null Log data or null if not found
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $logs = DatabaseHelper::select($sql, [$id], true);
        
        if (empty($logs)) {
            return null;
        }
        
        $log = $logs[0];
        $this->processLogData($log);
        
        return $log;
    }
    
    /**
     * Delete logs based on criteria
     *
     * @param array $filters Filter criteria
     * @param bool $forceBulkDelete Allow bulk deletion
     * @return int Number of deleted records
     */
    public function deleteLogs(array $filters, bool $forceBulkDelete = false): int
    {
        // Use LogQueryBuilder to build the where clause
        list($whereClause, $params) = LogQueryBuilder::buildWhereClause($filters);
        
        // Safety check
        if (empty($whereClause) || $whereClause === "1=1" && count($params) === 0 && !$forceBulkDelete) {
            throw new \Exception('Attempted to delete all logs without explicit confirmation');
        }
        
        // Get IDs to delete for batch processing
        $sql = "SELECT id FROM {$this->table} WHERE {$whereClause}";
        
        // Add limit for safety if not forced bulk delete
        if (!$forceBulkDelete) {
            $sql .= " LIMIT 10000";
        }
        
        $logIds = DatabaseHelper::select($sql, $params, true);
        $ids = array_column($logIds, 'id');
        
        if (empty($ids)) {
            return 0;
        }
        
        // Use batch processing to delete
        $totalDeleted = 0;
        $batches = array_chunk($ids, 1000);
        
        foreach ($batches as $batch) {
            $placeholders = implode(',', array_fill(0, count($batch), '?'));
            $deleteSql = "DELETE FROM {$this->table} WHERE id IN ({$placeholders})";
            $rowsDeleted = DatabaseHelper::execute($deleteSql, $batch, true);
            $totalDeleted += $rowsDeleted;
        }
        
        return $totalDeleted;
    }
    
    /**
     * Export logs to CSV
     *
     * @param array $filters Filter criteria
     * @return array Export file information
     */
    public function exportLogs(array $filters): array
    {
        // Create export file information
        $exportId = date('Ymd_His') . '_' . substr(uniqid(), -8);
        $filename = 'audit_logs_export_' . $exportId . '.csv';
        $exportDir = rtrim(sys_get_temp_dir(), '/') . '/secure_exports';
        
        // Ensure directory exists
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0750, true);
        }
        
        $filepath = $exportDir . '/' . $filename;
        
        // Get export query
        $exportQuery = LogQueryBuilder::buildExportQuery($filters);
        
        // Execute export
        $rowsExported = DatabaseHelper::executeExport($exportQuery['sql'], $exportQuery['params'], $filepath);
        
        // Set permissions
        chmod($filepath, 0640);
        
        // Calculate expiry
        $expiryTime = time() + (24 * 3600); // 24 hours
        
        return [
            'file_path' => $filepath,
            'file_name' => $filename,
            'export_id' => $exportId,
            'row_count' => $rowsExported,
            'expiry_time' => $expiryTime,
            'expiry_formatted' => date('Y-m-d H:i:s', $expiryTime)
        ];
    }
    
    /**
     * Create a new log entry
     *
     * @param array $data Log data
     * @return int|null ID of created log or null on failure
     */
    public function createLog(array $data): ?int
    {
        // Ensure required fields are present
        $requiredFields = ['action', 'message', 'log_level'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        // Format details as JSON if it's an array
        if (isset($data['details']) && is_array($data['details'])) {
            $data['details'] = json_encode($data['details'], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        
        // Add timestamp if not present
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        return DatabaseHelper::insert($this->table, $data, true);
    }
    
    /**
     * Process log data for output
     *
     * @param array &$log Log data to process
     */
    private function processLogData(array &$log): void
    {
        // Parse JSON details
        if (isset($log['details']) && is_string($log['details'])) {
            $log['details'] = json_decode($log['details'], true) ?? [];
        }
        
        // Format timestamp
        if (!empty($log['created_at'])) {
            $date = new DateTime($log['created_at']);
            $log['formatted_date'] = $date->format('Y-m-d H:i:s');
        }
    }
}
