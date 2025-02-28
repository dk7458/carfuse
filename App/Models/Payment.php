<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

/**
 * Payment Model
 *
 * Represents a payment transaction in the system.
 *
 * @property int $id Primary key
 * @property int $user_id ID of the user who made the payment
 * @property int $booking_id ID of the associated booking
 * @property float $amount Transaction amount
 * @property string $method Payment method (credit_card, PayPal, etc.)
 * @property string $status Status of the payment (pending, completed, failed)
 * @property string|null $transaction_id Unique external transaction identifier
 */
class Payment extends BaseModel
{
    protected $table = 'payments';
    protected $resourceName = 'payment';
    
    /**
     * Validation rules for the model.
     *
     * @var array
     */
    public static $rules = [
        'user_id' => 'required|exists:users,id',
        'booking_id' => 'required|exists:bookings,id',
        'amount' => 'required|numeric|min:0',
        'method' => 'required|string|in:credit_card,paypal,bank_transfer',
        'status' => 'required|string|in:pending,completed,failed',
        'transaction_id' => 'nullable|string|max:255',
    ];

    public function __construct(DatabaseHelper $dbHelper, AuditService $auditService = null)
    {
        $this->pdo = $dbHelper->getPdo();
        $this->auditService = $auditService;
    }

    /**
     * Find a payment by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all payments.
     *
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table} 
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create a new payment.
     *
     * @param array $data
     * @return int ID of the created payment
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (
                user_id, booking_id, amount, method, status, 
                transaction_id, created_at, updated_at
            ) VALUES (
                :user_id, :booking_id, :amount, :method, :status, 
                :transaction_id, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':booking_id' => $data['booking_id'],
            ':amount' => $data['amount'],
            ':method' => $data['method'],
            ':status' => $data['status'] ?? 'pending',
            ':transaction_id' => $data['transaction_id'] ?? null,
        ]);
        
        $paymentId = $this->pdo->lastInsertId();
        
        // Log audit if service is available
        if ($this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'Created payment', [
                'payment_id' => $paymentId,
                'user_id' => $data['user_id'],
                'booking_id' => $data['booking_id'],
                'amount' => $data['amount'],
                'method' => $data['method']
            ]);
        }
        
        return $paymentId;
    }

    /**
     * Update a payment.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['user_id', 'booking_id', 'amount', 'method', 'status', 'transaction_id'])) {
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
            UPDATE {$this->table} 
            SET $setClause 
            WHERE id = :id AND deleted_at IS NULL
        ");
        
        $result = $stmt->execute($params);
        
        // Log audit if service is available and update was successful
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'Updated payment', [
                'payment_id' => $id,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }

    /**
     * Soft delete a payment.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table} 
            SET deleted_at = NOW() 
            WHERE id = :id AND deleted_at IS NULL
        ");
        
        $result = $stmt->execute([':id' => $id]);
        
        // Log audit if service is available and delete was successful
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'Deleted payment', [
                'payment_id' => $id
            ]);
        }
        
        return $result;
    }

    /**
     * Get payments by user ID.
     * Replaces scopeByUser.
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
     * Get completed payments.
     * Replaces scopeCompleted.
     *
     * @return array
     */
    public function getCompleted(): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE status = 'completed' AND deleted_at IS NULL
            ORDER BY created_at DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get payments by status.
     * Replaces scopeByStatus.
     *
     * @param string $status
     * @return array
     */
    public function getByStatus(string $status): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE status = :status AND deleted_at IS NULL
            ORDER BY created_at DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':status' => $status]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get payments within a date range.
     * Replaces scopeByDateRange.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE created_at BETWEEN :start_date AND :end_date
            AND deleted_at IS NULL
            ORDER BY created_at DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get user data for a payment.
     * Replaces user relationship.
     *
     * @param int $paymentId
     * @return array|null
     */
    public function getUser(int $paymentId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.* FROM users u
            JOIN {$this->table} p ON u.id = p.user_id
            WHERE p.id = :payment_id AND p.deleted_at IS NULL
        ");
        $stmt->execute([':payment_id' => $paymentId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get booking data for a payment.
     * Replaces booking relationship.
     *
     * @param int $paymentId
     * @return array|null
     */
    public function getBooking(int $paymentId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.* FROM bookings b
            JOIN {$this->table} p ON b.id = p.booking_id
            WHERE p.id = :payment_id AND p.deleted_at IS NULL AND b.deleted_at IS NULL
        ");
        $stmt->execute([':payment_id' => $paymentId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
