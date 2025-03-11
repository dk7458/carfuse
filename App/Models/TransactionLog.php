<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
use App\Services\EncryptionService;
use Psr\Log\LoggerInterface;

/**
 * Transaction Log Model
 * 
 * Represents financial transaction log entries in the system with enhanced security
 * for sensitive financial data.
 */
class TransactionLog extends BaseModel
{
    protected $table = 'transaction_logs';
    protected $resourceName = 'transaction';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $useUuid = true;
    
    /**
     * Fields that should be encrypted when stored in the database
     *
     * @var array
     */
    protected $encryptedFields = ['amount', 'card_number', 'card_last4'];

    /**
     * Constructor
     * 
     * @param DatabaseHelper $dbHelper
     * @param AuditService $auditService
     * @param LoggerInterface $logger
     */
    public function __construct(DatabaseHelper $dbHelper, AuditService $auditService, LoggerInterface $logger)
    {
        parent::__construct($dbHelper, $auditService, $logger);
    }

    /**
     * Encrypt sensitive data before insert/update
     *
     * @param array $data
     * @return array
     */
    protected function encryptSensitiveData(array $data): array
    {
        if (!class_exists(EncryptionService::class)) {
            $this->logger->warning('EncryptionService not available, storing sensitive data unencrypted');
            return $data;
        }

        foreach ($this->encryptedFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = EncryptionService::encrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Decrypt sensitive data after fetch
     *
     * @param array $data
     * @return array
     */
    protected function decryptSensitiveData(array $data): array
    {
        if (!class_exists(EncryptionService::class)) {
            return $data;
        }

        foreach ($this->encryptedFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = EncryptionService::decrypt($data[$field]);
            }
        }
        
        return $data;
    }

    /**
     * Override BaseModel's find method to decrypt sensitive fields
     *
     * @param string|int $id
     * @return array|null
     */
    public function find(string|int $id): ?array
    {
        $result = parent::find($id);
        
        if ($result) {
            $result = $this->decryptSensitiveData($result);
        }
        
        return $result;
    }

    /**
     * Override BaseModel's all method to decrypt sensitive fields
     *
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function all(array $orderBy = ['created_at' => 'DESC'], ?int $limit = null, ?int $offset = null): array
    {
        $results = parent::all($orderBy, $limit, $offset);
        
        foreach ($results as &$row) {
            $row = $this->decryptSensitiveData($row);
        }
        
        return $results;
    }

    /**
     * Override BaseModel's create method to encrypt sensitive fields
     *
     * @param array $data
     * @return int|string
     */
    public function create(array $data): int|string
    {
        // Encrypt sensitive data before storing
        $data = $this->encryptSensitiveData($data);
        
        // Call parent create method
        $id = parent::create($data);
        
        // Add additional audit logging
        if ($this->auditService) {
            $this->auditService->logEvent(
                $this->resourceName,
                'transaction_created',
                [
                    'transaction_id' => $id,
                    'user_id' => $data['user_id'] ?? null,
                    'transaction_type' => $data['transaction_type'] ?? null,
                    'reference' => $data['reference'] ?? null
                ]
            );
        }
        
        return $id;
    }

    /**
     * Log a new financial transaction
     *
     * @param array $transactionData
     * @return int|string
     */
    public function logTransaction(array $transactionData): int|string
    {
        // Validate required fields
        $requiredFields = ['user_id', 'amount', 'transaction_type'];
        foreach ($requiredFields as $field) {
            if (!isset($transactionData[$field])) {
                $this->logger->error("Missing required field for transaction log: {$field}");
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        // Set default status if not provided
        if (!isset($transactionData['status'])) {
            $transactionData['status'] = 'pending';
        }
        
        // Generate reference number if not provided
        if (!isset($transactionData['reference'])) {
            $transactionData['reference'] = $this->generateReferenceNumber();
        }
        
        // Store the transaction
        return $this->create($transactionData);
    }
    
    /**
     * Update transaction status
     *
     * @param string|int $transactionId
     * @param string $status
     * @param array $additionalData
     * @return bool
     */
    public function updateStatus(string|int $transactionId, string $status, array $additionalData = []): bool
    {
        $data = ['status' => $status] + $additionalData;
        
        $result = parent::update($transactionId, $data);
        
        if ($result && $this->auditService) {
            $this->auditService->logEvent(
                $this->resourceName,
                'transaction_status_updated',
                [
                    'transaction_id' => $transactionId,
                    'new_status' => $status,
                    'previous_status' => $additionalData['previous_status'] ?? 'unknown'
                ]
            );
        }
        
        return $result;
    }
    
    /**
     * Get transactions for a specific user
     *
     * @param int|string $userId
     * @return array
     */
    public function getForUser(int|string $userId): array
    {
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $results = $this->dbHelper->select($query, [':user_id' => $userId]);
        
        // Decrypt any sensitive fields
        foreach ($results as &$row) {
            $row = $this->decryptSensitiveData($row);
        }
        
        return $results;
    }
    
    /**
     * Get transactions by reference number
     *
     * @param string $reference
     * @return array|null
     */
    public function getByReference(string $reference): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE reference = :reference";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $results = $this->dbHelper->select($query, [':reference' => $reference]);
        
        if (empty($results)) {
            return null;
        }
        
        // Decrypt any sensitive fields
        $results[0] = $this->decryptSensitiveData($results[0]);
        
        return $results[0];
    }
    
    /**
     * Generate a unique transaction reference number
     *
     * @return string
     */
    protected function generateReferenceNumber(): string
    {
        $prefix = 'TXN';
        $timestamp = date('YmdHis');
        $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Get transactions within a date range
     *
     * @param string $startDate Format: Y-m-d
     * @param string $endDate Format: Y-m-d
     * @param string|null $type
     * @return array
     */
    public function getByDateRange(string $startDate, string $endDate, ?string $type = null): array
    {
        $query = "SELECT * FROM {$this->table} 
                  WHERE created_at BETWEEN :start_date AND :end_date";
        
        $params = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];
        
        if ($type !== null) {
            $query .= " AND transaction_type = :type";
            $params[':type'] = $type;
        }
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $results = $this->dbHelper->select($query, $params);
        
        // Decrypt any sensitive fields
        foreach ($results as &$row) {
            $row = $this->decryptSensitiveData($row);
        }
        
        return $results;
    }
}
