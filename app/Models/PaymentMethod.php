<?php

namespace App\Models;

use PDO;

/**
 * PaymentMethod Model
 *
 * Represents a payment method and handles interactions with the `payment_methods` table.
 */
class PaymentMethod
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all available payment methods.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM payment_methods WHERE is_active = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get payment method by ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_methods WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Add a new payment method.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO payment_methods (name, description, is_active, created_at)
            VALUES (:name, :description, :is_active, NOW())
        ");
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? '',
            ':is_active' => $data['is_active'] ?? 1,
        ]);
        return $this->db->lastInsertId();
    }
}
