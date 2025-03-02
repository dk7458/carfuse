<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

/**
 * RefundLog Model
 *
 * Represents a refund and handles interactions with the `refund_logs` table.
 */
class RefundLog extends BaseModel
{
    protected $table = 'refund_logs';
    protected $resourceName = 'refund_log';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;

    /**
     * Get the user associated with the refund.
     *
     * @param int $refundId
     * @return array|null
     */
    public function getUser(int $refundId): ?array
    {
        $refund = $this->find($refundId);
        
        if (!$refund || !isset($refund['user_id'])) {
            return null;
        }
        
        $query = "SELECT * FROM users WHERE id = :user_id";
        $result = $this->dbHelper->select($query, [':user_id' => $refund['user_id']]);
        return $result[0] ?? null;
    }

    /**
     * Get the payment associated with the refund.
     *
     * @param int $refundId
     * @return array|null
     */
    public function getPayment(int $refundId): ?array
    {
        $refund = $this->find($refundId);
        
        if (!$refund || !isset($refund['payment_id'])) {
            return null;
        }
        
        $query = "SELECT * FROM payments WHERE id = :payment_id";
        $result = $this->dbHelper->select($query, [':payment_id' => $refund['payment_id']]);
        return $result[0] ?? null;
    }
}