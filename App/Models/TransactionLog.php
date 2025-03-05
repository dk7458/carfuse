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
     * Log a transaction - convenience method that uses create().
     * This method is used for consistency with service calls.
     *
     * @param array $transactionData
     * @return int The ID of the logged transaction
     */
    public function logTransaction(array $transactionData): int
    {
        // Apply any specific transaction logging logic here
        if (!isset($transactionData['created_at'])) {
            $transactionData['created_at'] = date('Y-m-d H:i:s');
        }

        // If a description is not provided, generate a generic one
        if (!isset($transactionData['description'])) {
            $type = $transactionData['type'] ?? 'transaction';
            $transactionData['description'] = ucfirst($type) . ' processed';
        }

        // Log this transaction
        if ($this->auditService && isset($transactionData['type'])) {
            $this->auditService->logEvent(
                $this->resourceName,
                $transactionData['type'] . '_logged',
                [
                    'payment_id' => $transactionData['payment_id'] ?? null,
                    'booking_id' => $transactionData['booking_id'] ?? null,
                    'amount' => $transactionData['amount'] ?? null,
                    'status' => $transactionData['status'] ?? null
                ]
            );
        }

        // Use the create method to insert the transaction record
        return $this->create($transactionData);
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
        $transactions = $this->dbHelper->select($query, [':user_id' => $userId]);

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
        $transaction = $this->dbHelper->select($query, [':id' => $id]);

        if ($transaction) {
            $transaction = $transaction[0] ?? null;
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
        $transactions = $this->dbHelper->select($query, [':limit' => $limit]);

        // Decrypt transaction details
        foreach ($transactions as &$transaction) {
            $transaction['amount'] = EncryptionService::decrypt($transaction['amount']);
        }

        return $transactions;
    }
}
