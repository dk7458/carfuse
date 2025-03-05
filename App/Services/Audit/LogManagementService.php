<?php

namespace App\Services\Audit;

use App\Helpers\DatabaseHelper;
use App\Helpers\LogQueryBuilder;
use App\Helpers\SecurityHelper;
use App\Helpers\ExceptionHandler;
use Psr\Log\LoggerInterface;
use Exception;
use DateTime;
use DateTimeImmutable;

class LogManagementService
{
    // Configuration constants
    private const MAX_EXPORT_ROWS = 10000;
    private const BATCH_DELETE_SIZE = 1000;
    private const EXPORT_EXPIRY_HOURS = 24;
    
    private LoggerInterface $logger;
    private string $requestId;
    private ExceptionHandler $exceptionHandler;
    
    public function __construct(LoggerInterface $logger, string $requestId, ExceptionHandler $exceptionHandler = null)
    {
        $this->logger = $logger;
        $this->requestId = $requestId;
        $this->exceptionHandler = $exceptionHandler ?? new ExceptionHandler($logger);
    }
    
    /**
     * CENTRALIZED LOG INSERTION - All log writes go through this method
     *
     * @param string $category The category of the event
     * @param string $message The log message
     * @param array $context Additional context data
     * @param int|null $userId Associated user ID
     * @param int|null $bookingId Associated booking ID
     * @param string|null $ipAddress Client IP address
     * @param string $logLevel Log level
     * @return int|null ID of the created log entry
     */
    public function createLogEntry(
        string $category, 
        string $message, 
        array $context = [], 
        ?int $userId = null, 
        ?int $bookingId = null, 
        ?string $ipAddress = null,
        string $logLevel = 'info'
    ): ?int {
        try {
            // Sanitize inputs
            $message = SecurityHelper::sanitizeString($message);
            $context = $this->sanitizeContext($context);
            
            // Ensure request_id is included
            $context['request_id'] = $this->requestId;
            
            // Add client IP if not provided
            if (empty($ipAddress)) {
                $ipAddress = $this->getClientIp();
            }
            
            // Capture current time
            $timestamp = new DateTimeImmutable();
            
            // Prepare data for insertion
            $data = [
                'action'             => $category,
                'message'            => $message,
                'details'            => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'user_reference'     => $userId,
                'booking_reference'  => $bookingId,
                'ip_address'         => $ipAddress,
                'created_at'         => $timestamp->format('Y-m-d H:i:s'),
                'log_level'          => $logLevel,
                'request_id'         => $this->requestId,
            ];
            
            // Insert with secure database and prepared statements
            return DatabaseHelper::insert('audit_logs', $data, true);
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to create log entry: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'category' => $category
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Retrieve logs from the audit_logs table with applied filters and pagination
     *
     * @param array $filters Various filters to apply (category, user_id, etc.)
     * @return array Paginated result containing logs and pagination metadata
     */
    public function getLogs(array $filters = []): array
    {
        try {
            // Get the query parts from LogQueryBuilder
            $query = LogQueryBuilder::buildSelectQuery($filters);
            
            // Execute the count query if pagination is needed
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
            
            // Execute the main query using DatabaseHelper
            $logs = DatabaseHelper::select(
                $query['mainSql'],
                $query['params'],
                true
            );
            
            // Process results - parse JSON and format dates
            foreach ($logs as &$log) {
                if (isset($log['details']) && is_string($log['details'])) {
                    $log['details'] = json_decode($log['details'], true) ?? [];
                }
                
                if (!empty($log['created_at'])) {
                    $date = new DateTime($log['created_at']);
                    $log['formatted_date'] = $date->format('Y-m-d H:i:s');
                }
            }
            
            // Build the result array
            $result = ['data' => $logs];
            
            // Add pagination data if needed
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
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to get logs: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'filters' => $filters
            ]);
            $this->exceptionHandler->handleException($e);
            return ['data' => [], 'pagination' => ['total' => 0]];
        }
    }
    
    /**
     * Delete logs from the audit_logs table based on specific filters
     * Uses batch processing with configurable batch size
     *
     * @param array $filters Filters to determine which logs to delete
     * @param bool $forceBulkDelete Set to true to bypass safeguards for bulk deletion
     * @return int Number of logs deleted
     */
    public function deleteLogs(array $filters, bool $forceBulkDelete = false): int
    {
        try {
            // Use LogQueryBuilder to build the where clause
            list($whereClause, $params) = LogQueryBuilder::buildWhereClause($filters);
            
            // Safety check: prevent accidental deletion of all logs
            if (empty($whereClause) || $whereClause === "1=1" && count($params) === 0 && !$forceBulkDelete) {
                $this->logger->warning("[LogManager] Attempted to delete all logs without confirmation", [
                    'request_id' => $this->requestId
                ]);
                throw new Exception('Attempted to delete all logs without explicit confirmation');
            }
            
            // Get IDs to delete for batch processing using DatabaseHelper
            $sql = "SELECT id FROM audit_logs WHERE {$whereClause}";
            
            // Add limit for safety if not forced bulk delete
            if (!$forceBulkDelete) {
                $sql .= " LIMIT " . self::MAX_EXPORT_ROWS;
            }
            
            $logIds = DatabaseHelper::select($sql, $params, true);
            $ids = array_column($logIds, 'id');
            
            if (empty($ids)) {
                return 0; // No matching logs to delete
            }
            
            // Log the deletion attempt
            $this->logger->info("[LogManager] Deleting logs", [
                'request_id' => $this->requestId,
                'count' => count($ids)
            ]);
            
            // Use batch processing to delete with optimized batch size
            $totalDeleted = 0;
            $batches = array_chunk($ids, self::BATCH_DELETE_SIZE);
            
            foreach ($batches as $batch) {
                $placeholders = implode(',', array_fill(0, count($batch), '?'));
                $deleteSql = "DELETE FROM audit_logs WHERE id IN ({$placeholders})";
                $rowsDeleted = DatabaseHelper::execute($deleteSql, $batch, true);
                $totalDeleted += $rowsDeleted;
            }
            
            return $totalDeleted;
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to delete logs: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'filters' => $filters
            ]);
            $this->exceptionHandler->handleException($e);
            return 0;
        }
    }
    
    /**
     * Export logs to a CSV file based on provided filters
     *
     * @param array $filters Filters to determine which logs to export
     * @return array Path info for the exported file
     */
    public function exportLogs(array $filters): array
    {
        try {
            // Create export file information
            $exportId = date('Ymd_His') . '_' . substr(uniqid(), -8);
            $filename = 'audit_logs_export_' . $exportId . '.csv';
            $exportDir = rtrim(sys_get_temp_dir(), '/') . '/secure_exports';
            
            // Ensure export directory exists with proper permissions
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0750, true);
            }
            
            $filepath = $exportDir . '/' . $filename;
            
            // Get export query from LogQueryBuilder
            $exportQuery = LogQueryBuilder::buildExportQuery($filters);
            
            // Execute export query through DatabaseHelper
            $rowsExported = DatabaseHelper::executeExport($exportQuery['sql'], $exportQuery['params'], $filepath);
            
            // Set appropriate permissions for the file
            chmod($filepath, 0640);
            
            // Calculate expiry time
            $expiryTime = time() + (self::EXPORT_EXPIRY_HOURS * 3600);
            
            // Return export information
            return [
                'file_path' => $filepath,
                'file_name' => $filename,
                'export_id' => $exportId,
                'row_count' => $rowsExported,
                'expiry_time' => $expiryTime,
                'expiry_formatted' => date('Y-m-d H:i:s', $expiryTime)
            ];
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to export logs: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'filters' => $filters
            ]);
            $this->exceptionHandler->handleException($e);
            throw $e; // Re-throw as this is a user-requested export operation
        }
    }
    
    /**
     * Get a single log entry by ID
     *
     * @param int $logId Log ID
     * @return array|null Log data or null if not found
     */
    public function getLogById(int $logId): ?array
    {
        try {
            $sql = "SELECT * FROM audit_logs WHERE id = ? LIMIT 1";
            $logs = DatabaseHelper::select($sql, [$logId], true);
            
            if (empty($logs)) {
                return null;
            }
            
            $log = $logs[0];
            
            // Parse JSON details if present
            if (isset($log['details']) && is_string($log['details'])) {
                $log['details'] = json_decode($log['details'], true) ?? [];
            }
            
            // Format timestamp
            if (!empty($log['created_at'])) {
                $date = new DateTime($log['created_at']);
                $log['formatted_date'] = $date->format('Y-m-d H:i:s');
            }
            
            return $log;
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to get log by ID: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'log_id' => $logId
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Sanitize context array to prevent sensitive data storage
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'auth', 'key', 'apiKey', 'api_key', 'credential', 'credit_card', 'card_number', 'cvv', 'ssn'];
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            if ($value === null) continue;
            
            $lower = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveKeys as $sKey) {
                if (strpos($lower, $sKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            $sanitized[$key] = $isSensitive 
                ? '[REDACTED]' 
                : (is_array($value) ? $this->sanitizeContext($value) : $value);
        }
        
        return $sanitized;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP'] as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
