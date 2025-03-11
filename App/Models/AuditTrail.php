<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

/**
 * AuditTrail Model
 *
 * Represents the audit trails stored in the database and provides methods
 * for accessing and filtering the logs.
 */
class AuditTrail extends BaseModel
{
    protected $table = 'audit_trails';
    protected $resourceName = 'audit_trail';
    protected $useTimestamps = true;
    protected $useSoftDeletes = false;

    /**
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [
        'user_id',
        'booking_id',
        'action',
        'message',
        'details'
    ];

    /**
     * @var array Data type casting definitions
     */
    protected $casts = [
        'user_id' => 'int',
        'booking_id' => 'int'
    ];

    /**
     * Retrieve audit trail records based on filters.
     *
     * @param array $filters An associative array of filters:
     *                       - 'user_id' (int): Filter by user ID.
     *                       - 'booking_id' (int): Filter by booking ID.
     *                       - 'action' (string): Filter by action type.
     *                       - 'start_date' (string): Filter by start date (YYYY-MM-DD).
     *                       - 'end_date' (string): Filter by end date (YYYY-MM-DD).
     * @return array An array of matching audit trail records.
     */
    public function getLogs(array $filters = []): array
    {
        $query = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['user_id'])) {
            $query .= " AND user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['booking_id'])) {
            $query .= " AND booking_id = :booking_id";
            $params[':booking_id'] = $filters['booking_id'];
        }

        if (!empty($filters['action'])) {
            $query .= " AND action = :action";
            $params[':action'] = $filters['action'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND created_at >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND created_at <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $query .= " ORDER BY created_at DESC";

        return $this->dbHelper->select($query, $params);
    }
}
