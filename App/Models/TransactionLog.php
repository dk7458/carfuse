<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\EncryptionService;

/**
 * TransactionLog Model
 *
 * Represents a financial transaction and handles interactions with the `transaction_logs` table.
 */
class TransactionLog extends BaseModel
{
    // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define query scopes
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Prevent modification of log entries after creation
    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            return false;
        });
    }

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all transactions for a user.
     */
    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM transaction_logs WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Decrypt transaction details
        foreach ($transactions as &$transaction) {
            $transaction['amount'] = EncryptionService::decrypt($transaction['amount']);
        }

        return $transactions;
    }

    /**
     * Get transaction by ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM transaction_logs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($transaction) {
            // Decrypt transaction details
            $transaction['amount'] = EncryptionService::decrypt($transaction['amount']);
        }

        return $transaction;
    }

    /**
     * Log a new transaction.
     */
    public function create(array $data): int
    {
        // Encrypt transaction details
        $data['amount'] = EncryptionService::encrypt($data['amount']);

        $stmt = $this->db->prepare("
            INSERT INTO transaction_logs (user_id, booking_id, amount, type, status, created_at)
            VALUES (:user_id, :booking_id, :amount, :type, :status, NOW())
        ");
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':booking_id' => $data['booking_id'],
            ':amount' => $data['amount'],
            ':type' => $data['type'],
            ':status' => $data['status'] ?? 'pending',
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Update transaction status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE transaction_logs SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }
}
