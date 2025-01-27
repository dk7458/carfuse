<?php

namespace App\Models;

use DateTime;
use PDO;

/**
 * Booking Model
 *
 * Represents a booking and handles database interactions.
 */
class Booking
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get booking by ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get bookings for a specific user.
     */
    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create a new booking.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, status, created_at)
            VALUES (:user_id, :vehicle_id, :pickup_date, :dropoff_date, :status, NOW())
        ");
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':vehicle_id' => $data['vehicle_id'],
            ':pickup_date' => $data['pickup_date'],
            ':dropoff_date' => $data['dropoff_date'],
            ':status' => $data['status'] ?? 'pending',
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Update booking status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }
}
