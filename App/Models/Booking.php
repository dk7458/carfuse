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
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
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
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get payment data for a booking.
     *
     * @param int $bookingId
     * @return array|null
     */
    public function getPayment(int $bookingId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.* FROM payments p
            WHERE p.booking_id = :booking_id AND p.deleted_at IS NULL
        ");
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
