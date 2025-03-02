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
     * Get active bookings.
     *
     * @return array
     */
    public function getActive(): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE status = 'confirmed' AND deleted_at IS NULL
            ORDER BY created_at DESC
        ";
        
        return $this->dbHelper->select($query);
    }

    /**
     * Get bookings by user ID.
     *
     * @param int $userId
     * @return array
     */
    public function getByUser(int $userId): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE user_id = :user_id AND deleted_at IS NULL
            ORDER BY created_at DESC
        ";
        
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Get user data for a booking.
     *
     * @param int $bookingId
     * @return array|null
     */
    public function getUser(int $bookingId): ?array
    {
        $query = "
            SELECT u.* FROM users u
            JOIN {$this->table} b ON u.id = b.user_id
            WHERE b.id = :booking_id AND b.deleted_at IS NULL
        ";
        
        $result = $this->dbHelper->select($query, [':booking_id' => $bookingId]);
        return $result[0] ?? null;
    }

    /**
     * Get vehicle data for a booking.
     *
     * @param int $bookingId
     * @return array|null
     */
    public function getVehicle(int $bookingId): ?array
    {
        $query = "
            SELECT v.* FROM vehicles v
            JOIN {$this->table} b ON v.id = b.vehicle_id
            WHERE b.id = :booking_id AND b.deleted_at IS NULL
        ";
        
        $result = $this->dbHelper->select($query, [':booking_id' => $bookingId]);
        return $result[0] ?? null;
    }

    /**
     * Get payment data for a booking.
     *
     * @param int $bookingId
     * @return array|null
     */
    public function getPayment(int $bookingId): ?array
    {
        $query = "
            SELECT p.* FROM payments p
            WHERE p.booking_id = :booking_id AND p.deleted_at IS NULL
        ";
        $result = $this->dbHelper->select($query, [':booking_id' => $bookingId]);
        return $result[0] ?? null;
    }
}
