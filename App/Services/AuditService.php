<?php

namespace App\Services;

use Exception;
use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

class AuditService
{
    // Define standard log categories as constants
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_AUTH = 'auth';
    public const CATEGORY_TRANSACTION = 'transaction';
    public const CATEGORY_BOOKING = 'booking';
    public const CATEGORY_USER = 'user';
    public const CATEGORY_ADMIN = 'admin';
    public const CATEGORY_DOCUMENT = 'document';
    public const CATEGORY_API = 'api';
    public const CATEGORY_SECURITY = 'security';

    public const DEBUG_MODE = true;
    private $db;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(LoggerInterface $logger, ExceptionHandler $exceptionHandler, DatabaseHelper $db)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;

        // Log the database instance being used
        $this->logger->info("AuditService initialized with database instance", [
            'database' => $db === DatabaseHelper::getSecureInstance() ? 'secure_db' : 'db'
        ]);
    }

    /**
     * Unified method to log events across the system to a single audit_logs table.
     *
     * @param string $category The category of the event (system, transaction, booking, etc.)
     * @param string $message Human-readable message describing the event
     * @param array $context Additional contextual data for the event
     * @param int|null $userId ID of the user associated with the event
     * @param int|null $bookingId ID of the booking associated with the event
     * @param string|null $ipAddress IP address of the user
     * @return void
     * @throws Exception If logging fails
     */
    public function logEvent(
        string $category, 
        string $message, 
        array $context = [], 
        ?int $userId = null, 
        ?int $bookingId = null, 
        ?string $ipAddress = null
    ): void {
        try {
            // Ensure category is standardized
            $category = strtolower(trim($category));
            
            // Prepare data for insertion using DatabaseHelper::insert instead of table()->insert
            $data = [
                'action'             => $category,  // Using action field to store category
                'message'            => $message,
                'details'            => json_encode($context, JSON_UNESCAPED_UNICODE),
                'user_reference'     => $userId,
                'booking_reference'  => $bookingId,
                'ip_address'         => $ipAddress,
                'created_at'         => date('Y-m-d H:i:s') // Replace now() function with PHP date
            ];
            
            // Log the data array before insertion
            $this->logger->info("[Audit] Data to be inserted: ", $data);
            
            // Log the query being executed
            $this->logger->info("[Audit] Executing query: INSERT INTO audit_logs", $data);
            
            // Use DatabaseHelper::insert with secure database
            $insertId = DatabaseHelper::insert('audit_logs', $data, true);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Audit] Logged {$category} event: {$message}", [
                    'user_reference' => $userId,
                    'booking_reference' => $bookingId,
                    'insert_id' => $insertId
                ]);
            }
        } catch (Exception $e) {
            $this->logger->error("[Audit] ❌ logEvent error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw new Exception('Failed to log event: ' . $e->getMessage());
        }
    }

    /**
     * Legacy method to log an action.
     * @deprecated Use logEvent() instead for new code
     */
    public function log(
        string $action,
        string $message,
        array $details = [],
        ?int $userId = null,
        ?int $bookingId = null,
        ?string $ipAddress = null
    ): void {
        // For backward compatibility, call the new unified method
        $this->logEvent($action, $message, $details, $userId, $bookingId, $ipAddress);
    }

    /**
     * Retrieve logs from the unified audit_logs table with applied filters.
     *
     * @param array $filters Various filters to apply (category, user_id, etc.)
     * @return array Paginated result containing logs and pagination metadata
     * @throws Exception If fetching logs fails
     */
    public function getLogs(array $filters = []): array
    {
        try {
            // Build WHERE clause and parameters for both count and select queries
            $whereClause = "1=1"; // Always true condition to start with
            $params = [];
            
            // Apply filters
            if (!empty($filters['user_id'])) {
                $whereClause .= " AND user_reference = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['booking_id'])) {
                $whereClause .= " AND booking_reference = ?";
                $params[] = $filters['booking_id'];
            }
            
            // Support both 'category' and 'action' fields
            if (!empty($filters['category'])) {
                $whereClause .= " AND action = ?";
                $params[] = $filters['category'];
            } elseif (!empty($filters['action'])) {
                $whereClause .= " AND action = ?";
                $params[] = $filters['action'];
            }
            
            // Date range filters
            if (!empty($filters['start_date'])) {
                $whereClause .= " AND created_at >= ?";
                $params[] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereClause .= " AND created_at <= ?";
                $params[] = $filters['end_date'];
            }
            
            // Message search
            if (!empty($filters['search'])) {
                $whereClause .= " AND message LIKE ?";
                $params[] = '%' . $filters['search'] . '%';
            }
            
            // Get total count first (for pagination)
            $countSql = "SELECT COUNT(*) as total FROM audit_logs WHERE {$whereClause}";
            $this->logger->info("[Audit] Executing count query: {$countSql}", $params);
            $countResult = DatabaseHelper::select($countSql, $params);
            $totalItems = isset($countResult[0]['total']) ? (int)$countResult[0]['total'] : 0;
            
            // Pagination parameters
            $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
            $perPage = isset($filters['per_page']) ? max(1, (int)$filters['per_page']) : 10;
            $offset = ($page - 1) * $perPage;
            $totalPages = ceil($totalItems / $perPage);
            
            // Custom sort options
            $allowedSortFields = ['id', 'action', 'message', 'user_reference', 'booking_reference', 'created_at'];
            $sortField = in_array($filters['sort_field'] ?? '', $allowedSortFields) ? $filters['sort_field'] : 'created_at';
            $sortOrder = strtoupper($filters['sort_order'] ?? 'desc') === 'ASC' ? 'ASC' : 'DESC';
            
            // Build and execute the main query
            $sql = "SELECT * FROM audit_logs WHERE {$whereClause} ORDER BY {$sortField} {$sortOrder} LIMIT {$perPage} OFFSET {$offset}";
            $this->logger->info("[Audit] Executing select query: {$sql}", $params);
            $logs = DatabaseHelper::select($sql, $params);
            
            // Process the results - parse JSON details
            foreach ($logs as &$log) {
                if (isset($log['details']) && is_string($log['details'])) {
                    $log['details'] = json_decode($log['details'], true);
                }
            }
            
            // Create a custom paginated result array
            return [
                'data' => $logs,
                'pagination' => [
                    'total' => $totalItems,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $totalItems),
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->error("[Audit] ❌ getLogs error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw new Exception('Failed to get logs: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a single log entry by ID from the audit_logs table.
     * 
     * @param int $logId The ID of the log entry
     * @return array|null The log entry
     * @throws Exception If the log entry is not found
     */
    public function getLogById(int $logId)
    {
        try {
            // Use DatabaseHelper::select instead of $this->db->table()->where()->first()
            $sql = "SELECT * FROM audit_logs WHERE id = ? LIMIT 1";
            $this->logger->info("[Audit] Executing select query: {$sql}", [$logId]);
            $logs = DatabaseHelper::select($sql, [$logId]);
            $log = !empty($logs) ? $logs[0] : null;
            
            if (!$log) {
                throw new Exception('Log entry not found.');
            }
            
            // Parse JSON details if present
            if (isset($log['details']) && is_string($log['details'])) {
                $log['details'] = json_decode($log['details'], true);
            }
            
            return $log;
        } catch (Exception $e) {
            $this->logger->error("[Audit] ❌ getLogById error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw new Exception('Failed to retrieve log: ' . $e->getMessage());
        }
    }

    /**
     * Delete logs from the audit_logs table based on specific filters.
     * 
     * @param array $filters Filters to determine which logs to delete
     * @return int Number of logs deleted
     * @throws Exception If deletion fails
     */
    public function deleteLogs(array $filters): int
    {
        try {
            // Build WHERE clause and parameters
            $whereClause = "1=1";
            $params = [];
            
            // Apply filters
            if (!empty($filters['user_id'])) {
                $whereClause .= " AND user_reference = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['booking_id'])) {
                $whereClause .= " AND booking_reference = ?";
                $params[] = $filters['booking_id'];
            }
            
            // Support both 'category' and 'action' fields
            if (!empty($filters['category'])) {
                $whereClause .= " AND action = ?";
                $params[] = $filters['category'];
            } elseif (!empty($filters['action'])) {
                $whereClause .= " AND action = ?";
                $params[] = $filters['action'];
            }
            
            // Date range filters
            if (!empty($filters['start_date'])) {
                $whereClause .= " AND created_at >= ?";
                $params[] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereClause .= " AND created_at <= ?";
                $params[] = $filters['end_date'];
            }
            
            // Use DatabaseHelper::safeQuery for custom DELETE query
            $sql = "DELETE FROM audit_logs WHERE {$whereClause}";
            $this->logger->info("[Audit] Executing delete query: {$sql}", $params);
            $deleted = DatabaseHelper::safeQuery(function ($pdo) use ($sql, $params) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->rowCount();
            });
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Audit] Deleted {$deleted} logs with filters: " . json_encode($filters));
            }
            
            return $deleted;
        } catch (Exception $e) {
            $this->logger->error("[Audit] ❌ deleteLogs error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw new Exception('Failed to delete logs: ' . $e->getMessage());
        }
    }
    
    /**
     * Export logs to a CSV file based on provided filters.
     * 
     * @param array $filters Filters to determine which logs to export
     * @return string Path to the exported CSV file
     * @throws Exception If export fails
     */
    public function exportLogs(array $filters): string
    {
        try {
            // Build WHERE clause and parameters
            $whereClause = "1=1";
            $params = [];
            
            // Apply the same filters as in getLogs
            if (!empty($filters['user_id'])) {
                $whereClause .= " AND user_reference = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['booking_id'])) {
                $whereClause .= " AND booking_reference = ?";
                $params[] = $filters['booking_id'];
            }
            
            if (!empty($filters['category'])) {
                $whereClause .= " AND action = ?";
                $params[] = $filters['category'];
            } elseif (!empty($filters['action'])) {
                $whereClause .= " AND action = ?";
                $params[] = $filters['action'];
            }
            
            if (!empty($filters['start_date'])) {
                $whereClause .= " AND created_at >= ?";
                $params[] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereClause .= " AND created_at <= ?";
                $params[] = $filters['end_date'];
            }
            
            // Use DatabaseHelper::select instead of query builder get()
            $sql = "SELECT * FROM audit_logs WHERE {$whereClause} ORDER BY created_at DESC";
            $this->logger->info("[Audit] Executing select query for export: {$sql}", $params);
            $logs = DatabaseHelper::select($sql, $params);
            
            // Create CSV file
            $filename = 'audit_logs_export_' . date('Y-m-d_His') . '.csv';
            $filepath = sys_get_temp_dir() . '/' . $filename;
            
            $file = fopen($filepath, 'w');
            
            // Write CSV header
            fputcsv($file, ['ID', 'Category', 'Message', 'User Reference', 'Booking Reference', 'IP Address', 'Created At', 'Details']);
            
            // Write data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log['id'],
                    $log['action'],
                    $log['message'],
                    $log['user_reference'],
                    $log['booking_reference'],
                    $log['ip_address'],
                    $log['created_at'],
                    $log['details'] // This will be JSON string already
                ]);
            }
            
            fclose($file);
            
            // Log the export action using our refactored logEvent method
            $this->logEvent(
                'system',
                'Audit logs exported',
                ['filters' => $filters, 'count' => count($logs)],
                $_SESSION['user_id'] ?? null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $filepath;
        } catch (Exception $e) {
            $this->logger->error("[Audit] ❌ exportLogs error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw new Exception('Failed to export logs: ' . $e->getMessage());
        }
    }
}
