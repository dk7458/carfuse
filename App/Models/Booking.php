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
     * Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'pickup_date',
        'dropoff_date',
        'status'
    ];

    /**
     * @var array Data type casting definitions
     */
    protected $casts = [
        'user_id' => 'int',
        'vehicle_id' => 'int',
        'pickup_date' => 'datetime',
        'dropoff_date' => 'datetime'
    ];
    
    /**
     * Constructor
     *
     * @param DatabaseHelper|null $dbHelper
     * @param AuditService|null $auditService
     */
    public function __construct(DatabaseHelper $dbHelper, AuditService $auditService)
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
     * Get bookings within a date range with optional filters
     *
     * @param string $start
     * @param string $end
     * @param array $filters
     * @return array
     */
    public function getByDateRange(string $start, string $end, array $filters = []): array
    {
        // Check if we're filtering by pickup/dropoff dates or creation dates
        $dateField = !empty($filters['date_field']) ? $filters['date_field'] : 'created_at';
        
        $query = "SELECT b.*, u.name as user_name, v.model as vehicle_model 
                 FROM {$this->table} b
                 LEFT JOIN users u ON b.user_id = u.id
                 LEFT JOIN vehicles v ON b.vehicle_id = v.id
                 WHERE b.{$dateField} BETWEEN :start AND :end";
        
        $params = [':start' => $start, ':end' => $end];
        
        if (!empty($filters['status'])) {
            $query .= " AND b.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        // Add sorting
        $query .= " ORDER BY b.{$dateField} ASC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get bookings for a specific user within a date range
     *
     * @param int $userId
     * @param string $start
     * @param string $end
     * @return array
     */
    public function getByUserAndDateRange(int $userId, string $start, string $end): array
    {
        $query = "SELECT b.*, v.model as vehicle_model 
                 FROM {$this->table} b
                 LEFT JOIN vehicles v ON b.vehicle_id = v.id
                 WHERE b.user_id = :user_id
                 AND b.created_at BETWEEN :start AND :end";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':start' => $start,
            ':end' => $end
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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

    /**
     * Get user ID for a booking
     * 
     * @param int|string $bookingId
     * @return int|null
     */
    public function getUserId(int|string $bookingId): ?int
    {
        $query = "
            SELECT user_id FROM {$this->table}
            WHERE id = :booking_id
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query, [':booking_id' => $bookingId]);
        return isset($result[0]['user_id']) ? (int)$result[0]['user_id'] : null;
    }

    /**
     * Get monthly booking trends
     * 
     * @return array
     */
    public function getMonthlyTrends(): array
    {
        $year = date('Y');
        $query = "
            SELECT 
                MONTH(created_at) as month,
                COUNT(*) as total
            FROM {$this->table}
            WHERE YEAR(created_at) = :year
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $query .= " GROUP BY MONTH(created_at) ORDER BY month ASC";
        
        return $this->dbHelper->select($query, [':year' => $year]);
    }

    /**
     * Get total number of bookings
     * 
     * @return int
     */
    public function getTotalBookings(): int
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if ($this->useSoftDeletes) {
            $query .= " WHERE deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query);
        return isset($result[0]['total']) ? (int)$result[0]['total'] : 0;
    }

    /**
     * Get number of completed bookings
     * 
     * @return int
     */
    public function getCompletedBookings(): int
    {
        $query = "
            SELECT COUNT(*) as total FROM {$this->table}
            WHERE status = 'completed'
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query);
        return isset($result[0]['total']) ? (int)$result[0]['total'] : 0;
    }
    
    /**
     * Get number of canceled bookings
     * 
     * @return int
     */
    public function getCanceledBookings(): int
    {
        $query = "
            SELECT COUNT(*) as total FROM {$this->table}
            WHERE status = 'canceled'
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query);
        return isset($result[0]['total']) ? (int)$result[0]['total'] : 0;
    }
    
    /**
     * Get booking logs for a specific booking
     * 
     * @param int|string $bookingId
     * @return array
     */
    public function getLogs(int|string $bookingId): array
    {
        $query = "
            SELECT * FROM booking_logs
            WHERE booking_id = :booking_id
            ORDER BY created_at DESC
        ";
        
        return $this->dbHelper->select($query, [':booking_id' => $bookingId]);
    }
    
    /**
     * Check if a booking is available based on vehicle and dates
     * 
     * @param int|string $vehicleId
     * @param string $pickupDate
     * @param string $dropoffDate
     * @return bool
     */
    public function isAvailable(int|string $vehicleId, string $pickupDate, string $dropoffDate): bool
    {
        return $this->isVehicleAvailable($vehicleId, $pickupDate, $dropoffDate);
    }
}
