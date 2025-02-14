<?php

namespace App\Services;

use App\Models\AuditLog;
use Exception;

class AuditService
{
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
            AuditLog::create([
                'action'     => $action,
                'details'    => json_encode($details, JSON_UNESCAPED_UNICODE),
                'user_id'    => $userId,
                'booking_id' => $bookingId,
                'ip_address' => $ipAddress,
                'created_at' => now(),
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to log action: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve logs using Eloquent with applied filters.
     */
    public function getLogs(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Ensure user permissions (implement as needed)
        // if (!auth()->user()->hasPermission('view_logs')) {
        //     throw new Exception('Unauthorized access.');
        // }
        $query = AuditLog::query();

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
    }

    /**
     * Retrieve a single log entry by ID.
     */
    public function getLogById(int $logId): AuditLog
    {
        $log = AuditLog::find($logId);
        if (!$log) {
            throw new Exception('Log entry not found.');
        }
        return $log;
    }

    /**
     * Soft delete logs based on specific filters.
     */
    public function deleteLogs(array $filters): int
    {
        try {
            $query = AuditLog::query();

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

            // Soft delete records; model must use SoftDeletes.
            return $query->delete();
        } catch (Exception $e) {
            throw new Exception('Failed to delete logs: ' . $e->getMessage());
        }
    }
}
