<?php

namespace App\Models;

use PDO;

/**
 * RefundLog Model
 *
 * Represents a refund and handles interactions with the `refund_logs` table.
 */
class RefundLog
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get refund by ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM refund_logs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all refunds for a booking.
     */
    public function getByBookingId(int $bookingId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM refund_logs WHERE booking_id = :booking_id");
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create a refund record.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO refund_logs (booking_id, amount, reason, status, created_at)
            VALUES (:booking_id, :amount, :reason, :status, NOW())
        ");
        $stmt->execute([
            ':booking_id' => $data['booking_id'],
            ':amount' => $data['amount'],
            ':reason' => $data['reason'] ?? '',
            ':status' => $data['status'] ?? 'pending',
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Update refund status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE refund_logs SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }
}
