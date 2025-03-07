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
class TransactionLog extends BaseFinancialModel
{
    protected $table = 'transaction_logs';
    protected $resourceName = 'transaction_log';
    protected $useTimestamps = true; // Transaction logs use timestamps
    protected $useSoftDeletes = false; // Transaction logs don't use soft deletes

    private DatabaseHelper $dbHelper;
    private ?AuditService $auditService;

    public function __construct(DatabaseHelper $dbHelper, ?AuditService $auditService = null)
    {
        $this->dbHelper = $dbHelper;
        $this->auditService = $auditService;
    }

    /**
     * Get the database helper instance.
     *
     * @return DatabaseHelper
     */
    public function getDbHelper(): DatabaseHelper
    {
        return $this->dbHelper;
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Log a transaction - this is the method that other services will call.
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

        // Log this transaction for security audit
        if ($this->auditService && isset($transactionData['type'])) {
            $this->recordAuditEvent(
                $transactionData['type'] . '_logged',
                [
                    'payment_id' => $transactionData['payment_id'] ?? null,
                    'booking_id' => $transactionData['booking_id'] ?? null,
                    'amount' => $transactionData['amount'] ?? null,
                    'status' => $transactionData['status'] ?? null
                ],
                $transactionData['user_id'] ?? null
            );
        }

        // Encrypt any sensitive data and create the transaction log
        $encryptedData = $this->encryptSensitiveData($transactionData);
        return $this->create($encryptedData);
    }

    /**
     * Create a new transaction log.
     *
     * @param array $data
     * @return int The ID of the created transaction log
     * @throws \Exception If creation fails
     */
    public function create(array $data): int
    {
        // Required fields check
        if (!isset($data['payment_id']) || !isset($data['amount'])) {
            throw new \Exception('Transaction log requires payment_id and amount');
        }
        
        // Add timestamps
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Insert the record
        $logId = $this->dbHelper->insert($this->table, $data);
        
        if (!$logId) {
            throw new \Exception('Failed to create transaction log');
        }
        
        return (int)$logId;
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
        $result = $this->dbHelper->select($query, [':id' => $id]);
        
        if (!empty($result)) {
            // Decrypt sensitive data before returning
            return $this->decryptSensitiveData($result[0]);
        }
        
        return null;
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
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->dbHelper->update($this->table, $data, ['id' => $id]);
        
        // Log the event
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'status_update', [
                'id' => $id,
                'status' => $status
            ]);
        }

        return (bool)$result;
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

    /**
     * Get transactions by payment ID.
     *
     * @param int $paymentId
     * @return array
     */
    public function getByPaymentId(int $paymentId): array
    {
        $query = "SELECT * FROM {$this->table} WHERE payment_id = :payment_id ORDER BY created_at DESC";
        return $this->dbHelper->select($query, [':payment_id' => $paymentId]);
    }

    /**
     * Get transactions by booking ID.
     *
     * @param int $bookingId
     * @return array
     */
    public function getByBookingId(int $bookingId): array
    {
        $query = "SELECT * FROM {$this->table} WHERE booking_id = :booking_id ORDER BY created_at DESC";
        return $this->dbHelper->select($query, [':booking_id' => $bookingId]);
    }
}
