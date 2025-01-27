<?php

namespace App\Services;

use PDO;

class TransactionService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get transactions by user ID.
     */
    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM transaction_logs WHERE user_id = :user_id ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Log a transaction.
     */
    public function create(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO transaction_logs (user_id, booking_id, amount, type, status, created_at)
            VALUES (:user_id, :booking_id, :amount, :type, :status, NOW())
        ");
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':booking_id' => $data['booking_id'],
            ':amount' => $data['amount'],
            ':type' => $data['type'],
            ':status' => $data['status'],
        ]);
    }
}
