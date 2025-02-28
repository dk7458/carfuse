<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
use App\Services\EncryptionService;

/**
 * TransactionLog Model
 *
 * Represents a financial transaction and handles interactions with the `transaction_logs` table.
 */
class TransactionLog extends BaseModel
{
    protected $table = 'transaction_logs';
    protected $resourceName = 'transaction_log';
    protected $useTimestamps = true; // Transaction logs use timestamps
    protected $useSoftDeletes = false; // Transaction logs don't use soft deletes

    /**
     * Create a new transaction log.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        // Encrypt transaction details
        $data['amount'] = EncryptionService::encrypt($data['amount']);

        return parent::create($data);
    }

    /**
     * Get transactions by user ID.
     *
     * @param int $userId
     * @return array
     */
    public function getByUserId(int $userId): array
    {
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Decrypt transaction details
        foreach ($transactions as &$transaction) {
            $transaction['amount'] = EncryptionService::decrypt($transaction['amount']);
        }

        return $transactions;
    }

    /**
     * Get transaction by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($transaction) {
            // Decrypt transaction details
            $transaction['amount'] = EncryptionService::decrypt($transaction['amount']);
        }

        return $transaction ?: null;
    }

    /**
     * Update transaction status.
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $result = parent::update($id, ['status' => $status]);

        // Log the event
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'status_update', [
                'id' => $id,
                'status' => $status
            ]);
        }

        return $result;
    }

    /**
     * Get recent transactions.
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 10): array
    {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':limit' => $limit]);
        $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Decrypt transaction details
        foreach ($transactions as &$transaction) {
            $transaction['amount'] = EncryptionService::decrypt($transaction['amount']);
        }

        return $transactions;
    }
}
