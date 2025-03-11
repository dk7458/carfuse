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
 * @property string $type Type of transaction ('payment' or 'refund')
 * @property string|null $refund_reason Reason for refund, if applicable
 * @property int|null $original_payment_id ID of the original payment (for refunds only)
 */
class Payment extends BaseModel
{
    protected $table = 'payments';
    protected $resourceName = 'payment';
    protected $useSoftDeletes = true;

    /**
     * Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'booking_id',
        'amount',
        'type',
        'status',
        'created_at',
        'updated_at'
    ];

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
        'type' => 'string|in:payment,refund',
        'refund_reason' => 'nullable|string|max:255',
        'original_payment_id' => 'nullable|integer|exists:payments,id',
    ];

    public function __construct(DatabaseHelper $dbHelper, AuditService $auditService = null)
    {
        $this->dbHelper = $dbHelper;
        $this->auditService = $auditService;
    }

    /**
     * Find a payment by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findPayment(int $id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $result = $this->dbHelper->select($query, [':id' => $id]);
        return $result[0] ?? null; // Return first result or null
    }
    

    /**
     * Get all payments.
     *
     * @return array
     */

    public function all(array $orderBy = ['created_at' => 'DESC'], ?int $limit = null, ?int $offset = null): array
    {
        return parent::all($orderBy, $limit, $offset);
    }

    /**
     * Create a new payment or refund record
     *
     * @param array $data
     * @return int|null ID of the created payment/refund, or null on failure
     */
    public function createPaymentRecord(array $data): ?int
    {
        // Set default type to 'payment' if not specified
        $data['type'] = $data['type'] ?? 'payment';
        
        // Validate the data
        if (isset($this->validator)) {
            $validation = $this->validator->validate($data, self::$rules);
            if ($validation->fails()) {
                if (isset($this->logger)) {
                    $this->logger->error('Payment validation failed', $validation->errors()->all());
                }
                return null;
            }
        }
        
        // For refunds, the amount should be negative
        if ($data['type'] === 'refund' && $data['amount'] > 0) {
            $data['amount'] = -1 * abs($data['amount']);
        }

        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $paymentId = parent::create($data);
        
        if (!$paymentId) {
            return null;
        }

        if ($this->auditService) {
            $eventType = ($data['type'] === 'refund') ? 'Created refund' : 'Created payment';
            
            $auditData = [
                'payment_id' => $paymentId,
                'user_id' => $data['user_id'],
                'booking_id' => $data['booking_id'],
                'amount' => $data['amount'],
                'method' => $data['method'],
                'type' => $data['type']
            ];
            
            if ($data['type'] === 'refund') {
                $auditData['refund_reason'] = $data['refund_reason'] ?? null;
                $auditData['original_payment_id'] = $data['original_payment_id'] ?? null;
            }
            
            $this->auditService->logEvent($this->resourceName, $eventType, $auditData);
        }
        
        return (int) $paymentId;
    }

    /**
     * Create a refund record.
     * 
     * @param array $refundData Must contain: user_id, booking_id, amount, method, original_payment_id
     * @return int|null ID of the created refund, or null on failure
     */
    public function createRefund(array $refundData): ?int
    {
        // Ensure the type is set to refund
        $refundData['type'] = 'refund';
        
        // Set status to completed by default if not specified
        if (!isset($refundData['status'])) {
            $refundData['status'] = 'completed';
        }
        
        // Ensure refund reason is set
        if (!isset($refundData['refund_reason'])) {
            $refundData['refund_reason'] = 'Refund processed';
        }
        
        // Validate required fields specific to refunds
        if (!isset($refundData['original_payment_id'])) {
            if (isset($this->logger)) {
                $this->logger->error('Refund creation failed: original_payment_id is required');
            }
            return null;
        }
        
        // Check if original payment exists
        $originalPayment = $this->find($refundData['original_payment_id']);
        if (!$originalPayment) {
            if (isset($this->logger)) {
                $this->logger->error('Refund creation failed: original payment not found', [
                    'original_payment_id' => $refundData['original_payment_id']
                ]);
            }
            return null;
        }
        
        // Ensure original payment is a payment, not a refund
        if ($originalPayment['type'] === 'refund') {
            if (isset($this->logger)) {
                $this->logger->error('Refund creation failed: cannot refund a refund', [
                    'original_payment_id' => $refundData['original_payment_id']
                ]);
            }
            return null;
        }
        
        // Check if original payment is already fully refunded
        $refundedAmount = $this->getRefundedAmount($refundData['original_payment_id']);
        $originalAmount = abs($originalPayment['amount']);
        $requestedRefundAmount = abs($refundData['amount']);
        
        if (($refundedAmount + $requestedRefundAmount) > $originalAmount) {
            if (isset($this->logger)) {
                $this->logger->error('Refund creation failed: refund amount exceeds original payment', [
                    'original_payment_id' => $refundData['original_payment_id'],
                    'original_amount' => $originalAmount,
                    'already_refunded' => $refundedAmount,
                    'requested_refund' => $requestedRefundAmount
                ]);
            }
            return null;
        }
        
        // Ensure refund amount is stored as negative
        $refundData['amount'] = -1 * abs($refundData['amount']);
        
        // Use the create method to insert the refund record
        $refundId = $this->create($refundData);
        
        if ($refundId && $this->auditService) {
            // Add specialized refund audit log
            $this->auditService->logEvent(
                $this->resourceName, 
                'refund_processed', 
                [
                    'refund_id' => $refundId,
                    'original_payment_id' => $refundData['original_payment_id'],
                    'user_id' => $refundData['user_id'],
                    'booking_id' => $refundData['booking_id'],
                    'amount' => $refundData['amount'],
                    'reason' => $refundData['refund_reason'],
                    'remaining_balance' => $originalAmount - ($refundedAmount + abs($refundData['amount']))
                ]
            );
        }
        
        return $refundId;
    }

    /**
     * Update a payment record with audit logging
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updatePaymentRecord(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = parent::update($id, $data);
        
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'Updated payment', [
                'payment_id' => $id,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }

    /**
     * Soft delete a payment with audit logging
     *
     * @param int $id
     * @return bool
     */
    public function softDeletePayment(int $id): bool
    {
        $result = parent::delete($id);

        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'Deleted payment', ['payment_id' => $id]);
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
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND deleted_at IS NULL ORDER BY created_at DESC";
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Get completed payments.
     * Replaces scopeCompleted.
     *
     * @return array
     */
    public function getCompleted(): array
    {
        $query = "SELECT * FROM {$this->table} WHERE status = 'completed' AND deleted_at IS NULL ORDER BY created_at DESC";
        return $this->dbHelper->select($query);
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
        $query = "SELECT * FROM {$this->table} WHERE status = :status AND deleted_at IS NULL ORDER BY created_at DESC";
        return $this->dbHelper->select($query, [':status' => $status]);
    }

    /**
     * Get payments within a date range with optional filters
     *
     * @param string $start
     * @param string $end
     * @param array $filters
     * @return array
     */
    public function getByDateRange(string $start, string $end, array $filters = []): array
    {
        $query = "SELECT p.*, u.name as user_name 
                 FROM {$this->table} p
                 LEFT JOIN users u ON p.user_id = u.id
                 WHERE p.created_at BETWEEN :start AND :end";

        if ($this->useSoftDeletes) {
            $query .= " AND p.deleted_at IS NULL";
        }
        
        $params = [':start' => $start, ':end' => $end];
        
        if (!empty($filters['type'])) {
            $query .= " AND p.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        // Add sorting
        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
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
        $query = "SELECT u.* FROM users u JOIN {$this->table} p ON u.id = p.user_id WHERE p.id = :payment_id AND p.deleted_at IS NULL";
        return $this->dbHelper->select($query, [':payment_id' => $paymentId]);
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
        $query = "SELECT b.* FROM bookings b JOIN {$this->table} p ON b.id = p.booking_id WHERE p.id = :payment_id AND p.deleted_at IS NULL AND b.deleted_at IS NULL";
        return $this->dbHelper->select($query, [':payment_id' => $paymentId]);
    }

    /**
     * Get payments by booking ID
     * 
     * @param int $bookingId
     * @return array
     */
    public function getByBooking(int $bookingId): array
    {
        $query = "SELECT * FROM {$this->table} WHERE booking_id = :booking_id AND deleted_at IS NULL ORDER BY created_at DESC";
        return $this->dbHelper->select($query, [':booking_id' => $bookingId]);
    }

    /**
     * Get refunds for a specific payment
     *
     * @param int $paymentId
     * @return array
     */
    public function getRefundsForPayment(int $paymentId): array
    {
        $query = "SELECT * FROM {$this->table} 
                  WHERE original_payment_id = :payment_id 
                  AND type = 'refund' 
                  AND deleted_at IS NULL
                  ORDER BY created_at DESC";
                  
        return $this->dbHelper->select($query, [':payment_id' => $paymentId]);
    }

    /**
     * Get all refunds
     *
     * @return array
     */
    public function getAllRefunds(): array
    {
        $query = "SELECT * FROM {$this->table} 
                  WHERE type = 'refund' 
                  AND deleted_at IS NULL
                  ORDER BY created_at DESC";
                  
        return $this->dbHelper->select($query);
    }

    /**
     * Check if a payment has been refunded
     *
     * @param int $paymentId
     * @return bool
     */
    public function hasRefunds(int $paymentId): bool
    {
        $query = "SELECT COUNT(*) as refund_count 
                  FROM {$this->table} 
                  WHERE original_payment_id = :payment_id 
                  AND type = 'refund' 
                  AND deleted_at IS NULL";
                  
        $result = $this->dbHelper->select($query, [':payment_id' => $paymentId]);
        return (int)$result[0]['refund_count'] > 0;
    }

    /**
     * Get total refunded amount for a payment
     *
     * @param int $paymentId
     * @return float
     */
    public function getRefundedAmount(int $paymentId): float
    {
        $query = "SELECT SUM(ABS(amount)) as total_refunded 
                  FROM {$this->table} 
                  WHERE original_payment_id = :payment_id 
                  AND type = 'refund' 
                  AND status = 'completed' 
                  AND deleted_at IS NULL";
                  
        $result = $this->dbHelper->select($query, [':payment_id' => $paymentId]);
        return (float)($result[0]['total_refunded'] ?? 0);
    }

    /**
     * Create a payment and return its ID
     * 
     * @param array $paymentData Payment details
     * @return int ID of created payment
     * @throws \Exception If creation fails
     */
    public function createPayment(array $paymentData): int
    {
        // Set default values if not provided
        $data = array_merge([
            'status' => 'pending',
            'type' => 'payment',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], $paymentData);
        
        // Encrypt sensitive data
        $data = $this->encryptSensitiveData($data);
        
        // Create the payment record
        $paymentId = $this->create($data);
        
        if (!$paymentId) {
            throw new \Exception('Payment creation failed');
        }
        
        // Log the payment creation for security auditing
        if ($this->auditService) {
            $this->auditService->logEvent(
                $this->resourceName,
                'payment_created',
                [
                    'payment_id' => $paymentId,
                    'user_id' => $data['user_id'],
                    'amount' => $paymentData['amount'], // Use unencrypted amount for audit
                    'method' => $data['method'],
                    'status' => $data['status']
                ],
                $data['user_id'] ?? null
            );
        }
        
        return $paymentId;
    }

    /**
     * Get payments for a specific user within a date range
     *
     * @param int $userId
     * @param string $start
     * @param string $end
     * @return array
     */
    public function getByUserAndDateRange(int $userId, string $start, string $end): array
    {
        $query = "SELECT * FROM {$this->table} 
                 WHERE user_id = :user_id
                 AND created_at BETWEEN :start AND :end";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':start' => $start,
            ':end' => $end
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
