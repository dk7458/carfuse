<?php

namespace App\Models;

use PDO;
use App\Models\BaseModel;
use App\Models\User;

/**
 * PaymentMethod Model
 *
 * Represents a payment method and handles interactions with the `payment_methods` table.
 */
class PaymentMethod extends BaseModel
{
    protected $fillable = ['name', 'description', 'is_active', 'user_id', 'payment_type'];
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
        $validPaymentTypes = ['credit_card', 'paypal', 'bank_transfer'];
        if (!in_array($data['payment_type'], $validPaymentTypes)) {
            throw new \InvalidArgumentException("Invalid payment type.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO payment_methods (name, description, is_active, created_at, user_id, payment_type)
            VALUES (:name, :description, :is_active, NOW(), :user_id, :payment_type)
        ");
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? '',
            ':is_active' => $data['is_active'] ?? 1,
            ':user_id' => $data['user_id'],
            ':payment_type' => $data['payment_type'],
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Define relationship with User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
