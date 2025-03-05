<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

/**
 * Booking Model
 *
 * Represents a booking and handles database interactions.
 */
class Booking extends BaseModel
{
    protected $table = 'bookings';
    protected $resourceName = 'booking';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    
    /**
     * Constructor
     *
     * @param DatabaseHelper|null $dbHelper
     * @param AuditService|null $auditService
     */
    public function __construct(DatabaseHelper $dbHelper = null, AuditService $auditService = null)
    {
        parent::__construct($dbHelper, $auditService);
    }
    
    /**
     * Validation rules for the model.
     *
     * @var array
     */
    public static $rules = [
        'user_id' => 'required|exists:users,id',
        'vehicle_id' => 'required|exists:vehicles,id',
        'pickup_date' => 'required|date',
        'dropoff_date' => 'required|date|after_or_equal:pickup_date',
        'status' => 'required|string|in:pending,confirmed,cancelled,completed',
    ];

    /**
     * Create a new booking
     * 
     * @param array $data
     * @return int|string
     */
    public function create(array $data): int|string
    {
        $id = parent::create($data);
        
        // Custom audit logging
        if ($id && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'booking_created', [
                'booking_id' => $id,
                'user_id' => $data['user_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'status' => $data['status'] ?? null
            ]);
        }
        
        return $id;
    }

    /**
     * Update a booking
     * 
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        $result = parent::update($id, $data);
        
        // Custom audit logging
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'booking_updated', [
                'booking_id' => $id,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }

    /**
     * Update a booking's status
     * 
     * @param int|string $id
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(int|string $id, string $newStatus): bool
    {
        // Validate the status value
        $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed', 'paid'];
        if (!in_array($newStatus, $validStatuses)) {
            if (isset($this->logger)) {
                $this->logger->error("Invalid booking status: {$newStatus}");
            }
            return false;
        }
        
        // Use the existing update method to update just the status field
        return $this->update($id, ['status' => $newStatus]);
    }

    /**
     * Get active bookings.
     *
     * @return array
     */
    public function getActive(): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE status = 'confirmed'
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $this->dbHelper->select($query);
    }

    /**
     * Get bookings by user ID.
     *
     * @param int|string $userId
     * @return array
     */
    public function getByUser(int|string $userId): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE user_id = :user_id
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Get bookings by status
     * 
     * @param string $status
     * @return array
     */
    public function getByStatus(string $status): array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE status = :status
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $this->dbHelper->select($query, [':status' => $status]);
    }

    /**
     * Get bookings by date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE pickup_date >= :start_date AND dropoff_date <= :end_date
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $query .= " ORDER BY pickup_date ASC";
        
        return $this->dbHelper->select($query, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
    }

    /**
     * Get user data for a booking.
     *
     * @param int|string $bookingId
     * @return array|null
     */
    public function getUser(int|string $bookingId): ?array
    {
        $query = "
            SELECT u.* FROM users u
            JOIN {$this->table} b ON u.id = b.user_id
            WHERE b.id = :booking_id
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND b.deleted_at IS NULL AND u.deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query, [':booking_id' => $bookingId]);
        return $result ? $result[0] : null;
    }

    /**
     * Get vehicle data for a booking.
     *
     * @param int|string $bookingId
     * @return array|null
     */
    public function getVehicle(int|string $bookingId): ?array
    {
        $query = "
            SELECT v.* FROM vehicles v
            JOIN {$this->table} b ON v.id = b.vehicle_id
            WHERE b.id = :booking_id
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND b.deleted_at IS NULL AND v.deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query, [':booking_id' => $bookingId]);
        return $result ? $result[0] : null;
    }

    /**
     * Get payment data for a booking.
     *
     * @param int|string $bookingId
     * @return array|null
     */
    public function getPayment(int|string $bookingId): ?array
    {
        $query = "
            SELECT p.* FROM payments p
            WHERE p.booking_id = :booking_id
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND p.deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query, [':booking_id' => $bookingId]);
        return $result ? $result[0] : null;
    }
    
    /**
     * Check if a vehicle is available during a specific date range
     *
     * @param int|string $vehicleId
     * @param string $startDate
     * @param string $endDate
     * @param int|string|null $excludeBookingId Booking ID to exclude from check (for updates)
     * @return bool
     */
    public function isVehicleAvailable(int|string $vehicleId, string $startDate, string $endDate, int|string $excludeBookingId = null): bool
    {
        $query = "
            SELECT COUNT(*) as booking_count 
            FROM {$this->table}
            WHERE vehicle_id = :vehicle_id
            AND status IN ('pending', 'confirmed')
            AND NOT (
                dropoff_date < :start_date OR pickup_date > :end_date
            )
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        if ($excludeBookingId) {
            $query .= " AND id != :exclude_id";
        }
        
        $result = $this->dbHelper->select($query, [
            ':vehicle_id' => $vehicleId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':exclude_id' => $excludeBookingId
        ]);
        
        return $result[0]['booking_count'] == 0;
    }
}
