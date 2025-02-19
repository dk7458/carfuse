<?php

namespace App\Services;

use App\Models\AuditLog;
use Exception;
use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;

class AuditService
{
    private $db;
    private $logger;

    public function __construct()
    {
        $this->db = DatabaseHelper::getInstance();
        $this->logger = getLogger('audit.log');
    }

    /**
     * Log an action using AuditLog Eloquent model.
     */
    public function log(
        string $action,
        array $details = [],
        ?int $userId = null,
        ?int $bookingId = null,
        ?string $ipAddress = null
    ): void {
        try {
            $this->db->table('audit_logs')->insert([
                'action'     => $action,
                'details'    => json_encode($details, JSON_UNESCAPED_UNICODE),
                'user_id'    => $userId,
                'booking_id' => $bookingId,
                'ip_address' => $ipAddress,
                'created_at' => now()
            ]);
            $this->logger->info("✅ [AuditService] Logged action: {$action}");
        } catch (Exception $e) {
            $this->logger->error("❌ [AuditService] Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new Exception('Failed to log action: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve logs using Eloquent with applied filters.
     */
    public function getLogs(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $query = $this->db->table('audit_logs');
            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            if (!empty($filters['booking_id'])) {
                $query->where('booking_id', $filters['booking_id']);
            }
            if (!empty($filters['action'])) {
                $query->where('action', $filters['action']);
            }
            if (!empty($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            if (!empty($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }
            return $query->orderBy('created_at', 'desc')->paginate(10);
        } catch (Exception $e) {
            $this->logger->error("❌ [AuditService] Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    /**
     * Retrieve a single log entry by ID.
     */
    public function getLogById(int $logId)
    {
        try {
            $log = $this->db->table('audit_logs')->where('id', $logId)->first();
            if (!$log) {
                throw new Exception('Log entry not found.');
            }
            return $log;
        } catch (Exception $e) {
            $this->logger->error("❌ [AuditService] Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    /**
     * Soft delete logs based on specific filters.
     */
    public function deleteLogs(array $filters): int
    {
        try {
            $query = $this->db->table('audit_logs');
            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            if (!empty($filters['booking_id'])) {
                $query->where('booking_id', $filters['booking_id']);
            }
            if (!empty($filters['action'])) {
                $query->where('action', $filters['action']);
            }
            if (!empty($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            if (!empty($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }
            $deleted = $query->delete();
            $this->logger->info("✅ [AuditService] Deleted logs with filters: " . json_encode($filters));
            return $deleted;
        } catch (Exception $e) {
            $this->logger->error("❌ [AuditService] Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new Exception('Failed to delete logs: ' . $e->getMessage());
        }
    }
}
