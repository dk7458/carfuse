<?php

namespace App\Models;

use App\Services\DatabaseHelper;

/**
 * PaymentMethod Model
 *
 * Represents a payment method and handles interactions with the `payment_methods` table.
 */
class PaymentMethod extends BaseModel
{
    protected $table = 'payment_methods';
    protected $resourceName = 'payment_method';
    
    /**
     * Validation rules for the model.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
        'user_id' => 'required|exists:users,id',
        'payment_type' => 'required|string|in:credit_card,paypal,bank_transfer'
    ];

    public function __construct(DatabaseHelper $dbHelper)
    {
        $this->pdo = $dbHelper->getPdo();
    }

    /**
     * Get all available payment methods.
     */
    public function getAll(): array
    {
        $query = "SELECT * FROM {$this->table} WHERE is_active = 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get payment method by ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_methods WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
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

        return parent::create($data);
    }
    
    /**
     * Update a payment method.
     */
    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'description', 'is_active', 'payment_type'])) {
                $setClauses[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($setClauses)) {
            return false;
        }

        $setClauses[] = "updated_at = NOW()";
        $setClause = implode(', ', $setClauses);

        $stmt = $this->pdo->prepare("
            UPDATE payment_methods 
            SET $setClause 
            WHERE id = :id
        ");
        return $stmt->execute($params);
    }
    
    /**
     * Delete a payment method.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM payment_methods WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get payment methods by user ID.
     * Replaces scopeByUser.
     */
    public function getByUser(int $userId): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get user data for a payment method.
     * Replaces user relationship.
     */
    public function getUser(int $paymentMethodId): ?array
    {
        $query = "
            SELECT u.* FROM users u
            JOIN {$this->table} pm ON u.id = pm.user_id
            WHERE pm.id = :payment_method_id
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':payment_method_id' => $paymentMethodId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
