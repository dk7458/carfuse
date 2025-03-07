<?php

namespace App\Services\Payment;

use App\Models\TransactionLog;
use App\Models\Payment;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Exception;

class TransactionService
{
    /**
     * Handles transaction consistency, logging, and history retrieval.
     */
    private TransactionLog $transactionLogModel;
    private Payment $paymentModel;
    private AuditService $auditService;
    private LoggerInterface $logger;

    /**
     * Constructor to inject dependencies
     */
    public function __construct(
        TransactionLog $transactionLogModel,
        Payment $paymentModel,
        AuditService $auditService,
        LoggerInterface $logger
    ) {
        $this->transactionLogModel = $transactionLogModel;
        $this->paymentModel = $paymentModel;
        $this->auditService = $auditService;
        $this->logger = $logger;
    }

    /**
     * Logs a transaction in the system (payments, refunds, chargebacks, etc.).
     *
     * @param array $transactionData
     * @return array
     *   Confirmation data about the logged transaction
     */
    public function logTransaction(array $transactionData): array
    {
        try {
            // Validate the transaction data first
            if (!isset($transactionData['payment_id']) || !isset($transactionData['amount'])) {
                throw new Exception('Transaction data missing required fields');
            }
            
            // Store the transaction
            $logId = $this->transactionLogModel->logTransaction($transactionData);

            // If the transaction is security-sensitive, record it in the audit trail as well
            if (isset($transactionData['type']) && $this->isSecuritySensitive($transactionData['type'])) {
                $this->auditService->recordTransaction($transactionData);
            }

            // Optional: debug log
            $this->logger->info('Transaction logged successfully', [
                'transaction_log_id' => $logId,
                'transaction_data'   => $transactionData,
            ]);

            return [
                'status' => 'success',
                'log_id' => $logId,
                'message' => 'Transaction logged successfully.'
            ];
        } catch (Exception $e) {
            $this->logger->error('Error logging transaction', [
                'error' => $e->getMessage(),
                'transaction_data' => $transactionData,
            ]);
            throw $e;
        }
    }

    /**
     * Retrieves transaction history for a specific user.
     *
     * @param int $userId
     * @return array
     */
    public function getHistoryByUser(int $userId): array
    {
        try {
            $history = $this->transactionLogModel->getByUserId($userId);

            // Debug log
            $this->logger->info("Retrieved transaction history for user {$userId}", [
                'history_count' => count($history)
            ]);

            // Log the access in the audit trail
            $this->auditService->logEvent(
                'transaction', 
                'history_accessed',
                ['user_id' => $userId],
                $userId,
                null
            );

            return $history;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving transaction history', [
                'userId' => $userId,
                'error'  => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Retrieves transaction history with admin filters (date range, type, etc.).
     *
     * @param array $filters
     * @return array
     */
    public function getHistoryAdmin(array $filters): array
    {
        try {
            // Build a query based on filters
            $query = "SELECT * FROM {$this->transactionLogModel->getTable()} WHERE 1=1";
            $params = [];
            
            // Add date filters
            if (!empty($filters['date_from'])) {
                $query .= " AND created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $query .= " AND created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            // Add type filter
            if (!empty($filters['type'])) {
                $query .= " AND type = :type";
                $params[':type'] = $filters['type'];
            }
            
            // Add status filter
            if (!empty($filters['status'])) {
                $query .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Add order by
            $query .= " ORDER BY created_at DESC";
            
            // Execute the query
            $history = $this->transactionLogModel->getDbHelper()->select($query, $params);
            
            $this->logger->info('Retrieved admin transaction history', [
                'filters' => $filters,
                'history_count' => count($history)
            ]);
            
            // Log admin access to transaction history
            if (isset($filters['admin_id'])) {
                $this->auditService->logEvent(
                    'transaction', 
                    'admin_history_accessed',
                    ['admin_id' => $filters['admin_id'], 'filters' => $filters],
                    $filters['admin_id'],
                    null
                );
            }

            return $history;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving admin transaction history', [
                'filters' => $filters,
                'error'   => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Retrieve transaction details
     * 
     * @param int $transactionId
     * @param int $userId Requesting user ID
     * @param bool $isAdmin Whether the user is an admin
     * @return array|null Transaction details or null if not authorized
     */
    public function getTransactionDetails(int $transactionId, int $userId, bool $isAdmin = false): ?array
    {
        try {
            // Get the transaction from the model
            $transaction = $this->transactionLogModel->getById($transactionId);
            
            if (!$transaction) {
                return null;
            }
            
            // Check if user has permission
            if (!$isAdmin && $transaction['user_id'] != $userId) {
                $this->logger->warning('Unauthorized transaction access attempt', [
                    'transaction_id' => $transactionId,
                    'requesting_user_id' => $userId
                ]);
                return null;
            }
            
            // For payments, get the associated payment details
            if (isset($transaction['payment_id'])) {
                $payment = $this->paymentModel->find($transaction['payment_id']);
                if ($payment) {
                    $transaction['payment_details'] = $payment;
                }
            }
            
            // Log the legitimate access
            $this->auditService->logEvent(
                'transaction', 
                'details_accessed',
                [
                    'transaction_id' => $transactionId,
                    'user_id' => $userId,
                    'is_admin' => $isAdmin ? 'yes' : 'no'
                ],
                $userId,
                null
            );
            
            return $transaction;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving transaction details', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Decide whether a particular transaction type is security-sensitive.
     *
     * @param string $transactionType
     * @return bool
     */
    private function isSecuritySensitive(string $transactionType): bool
    {
        // Example: mark refunds, chargebacks, or large payments as sensitive
        $sensitiveTypes = ['refund', 'chargeback', 'dispute', 'auth_failure'];
        return in_array($transactionType, $sensitiveTypes, true);
    }
}
