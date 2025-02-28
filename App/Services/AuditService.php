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
            
            $this->db->table('audit_logs')->insert([
                'action'     => $category,  // Using action field to store category
                'message'    => $message,
                'details'    => json_encode($context, JSON_UNESCAPED_UNICODE),
                'user_id'    => $userId,
                'booking_id' => $bookingId,
                'ip_address' => $ipAddress,
                'created_at' => now()
            ]);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Audit] Logged {$category} event: {$message}", [
                    'user_id' => $userId,
                    'booking_id' => $bookingId
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
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws Exception If fetching logs fails
     */
    public function getLogs(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $query = $this->db->table('audit_logs');
            
            // Apply filters
            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            
            if (!empty($filters['booking_id'])) {
                $query->where('booking_id', $filters['booking_id']);
            }
            
            // Support both 'category' and 'action' fields (they map to the same DB column)
            if (!empty($filters['category'])) {
                $query->where('action', $filters['category']);
            } elseif (!empty($filters['action'])) {
                $query->where('action', $filters['action']);
            }
            
            // Date range filters
            if (!empty($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            
            if (!empty($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }
            
            // Message search
            if (!empty($filters['search'])) {
                $query->where('message', 'LIKE', '%' . $filters['search'] . '%');
            }
            
            // Custom sort options
            $sortField = $filters['sort_field'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortField, $sortOrder);
            
            // Paginate results
            $perPage = $filters['per_page'] ?? 10;
            
            return $query->paginate($perPage);
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
     * @return object The log entry
     * @throws Exception If the log entry is not found
     */
    public function getLogById(int $logId)
    {
        try {
            $log = $this->db->table('audit_logs')->where('id', $logId)->first();
            if (!$log) {
                throw new Exception('Log entry not found.');
            }
            
            // Parse JSON details if present
            if (isset($log->details) && is_string($log->details)) {
                $log->details = json_decode($log->details);
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
            $query = $this->db->table('audit_logs');
            
            // Apply filters
            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            
            if (!empty($filters['booking_id'])) {
                $query->where('booking_id', $filters['booking_id']);
            }
            
            // Support both 'category' and 'action' fields
            if (!empty($filters['category'])) {
                $query->where('action', $filters['category']);
            } elseif (!empty($filters['action'])) {
                $query->where('action', $filters['action']);
            }
            
            // Date range filters
            if (!empty($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            
            if (!empty($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }
            
            $deleted = $query->delete();
            
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
            // Get logs without pagination
            $query = $this->db->table('audit_logs');
            
            // Apply the same filters as in getLogs
            // ...apply filters based on $filters array...
            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            
            if (!empty($filters['booking_id'])) {
                $query->where('booking_id', $filters['booking_id']);
            }
            
            if (!empty($filters['category'])) {
                $query->where('action', $filters['category']);
            } elseif (!empty($filters['action'])) {
                $query->where('action', $filters['action']);
            }
            
            // Get results
            $logs = $query->orderBy('created_at', 'desc')->get();
            
            // Create CSV file
            $filename = 'audit_logs_export_' . date('Y-m-d_His') . '.csv';
            $filepath = sys_get_temp_dir() . '/' . $filename;
            
            $file = fopen($filepath, 'w');
            
            // Write CSV header
            fputcsv($file, ['ID', 'Category', 'Message', 'User ID', 'Booking ID', 'IP Address', 'Created At', 'Details']);
            
            // Write data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->action,
                    $log->message,
                    $log->user_id,
                    $log->booking_id,
                    $log->ip_address,
                    $log->created_at,
                    $log->details
                ]);
            }
            
            fclose($file);
            
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
