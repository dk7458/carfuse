<?php

namespace App\Services;

use App\Models\TransactionLog;
use App\Services\AuditService;
use App\Logging\LoggerInterface;
use Exception;

class TransactionService
{
    /**
     * Handles transaction consistency, logging, and history retrieval.
     *
     * Responsibilities:
     *  - Log all transactions in `TransactionLog` (both payments and refunds)
     *  - Provide a method for retrieving transaction histories for users/admins
     *  - Use AuditService for security-sensitive transactions
     *  - Use LoggerInterface for debugging and general transaction info
     *
     * @author
     * @version 1.0
     */
    private TransactionLog $transactionLogModel;
    private AuditService $auditService;
    private LoggerInterface $logger;

    /**
     * Constructor to inject the `TransactionLog` model, `AuditService`, and `LoggerInterface`.
     */
    public function __construct(
        TransactionLog $transactionLogModel,
        AuditService $auditService,
        LoggerInterface $logger
    ) {
        $this->transactionLogModel = $transactionLogModel;
        $this->auditService = $auditService;
        $this->logger = $logger;
    }

    /**
     * Logs a transaction in the system (payments, refunds, chargebacks, etc.).
     *
     * @param array $transactionData
     *   Example:
     *   [
     *       'payment_id'    => 123,
     *       'booking_id'    => 555,
     *       'refund_id'     => null,
     *       'amount'        => 100.00,
     *       'currency'      => 'USD',
     *       'status'        => 'completed',
     *       'description'   => 'Test transaction log.',
     *       'type'          => 'payment', // or 'refund', 'chargeback', etc.
     *   ]
     * @return array
     *   Confirmation data about the logged transaction
     */
    public function logTransaction(array $transactionData): array
    {
        try {
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
     *   An array of transaction records
     */
    public function getHistoryByUser(int $userId): array
    {
        try {
            $history = $this->transactionLogModel->getTransactionsByUser($userId);

            // Debug log
            $this->logger->info("Retrieved transaction history for user {$userId}", [
                'history_count' => count($history)
            ]);

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
     * Retrieves transaction history for an admin view, possibly with filters.
     *
     * @param array $filters
     *   Example: ['date_from' => '2024-01-01', 'date_to' => '2024-01-31', 'type' => 'refund']
     * @return array
     */
    public function getHistoryAdmin(array $filters): array
    {
        try {
            $history = $this->transactionLogModel->getTransactionsAdmin($filters);

            $this->logger->info('Retrieved admin transaction history', [
                'filters' => $filters,
                'history_count' => count($history)
            ]);

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
     * Decide whether a particular transaction type is security-sensitive.
     *
     * @param string $transactionType
     * @return bool
     */
    private function isSecuritySensitive(string $transactionType): bool
    {
        // Example: mark refunds, chargebacks, or large payments as sensitive
        $sensitiveTypes = ['refund', 'chargeback'];
        return in_array($transactionType, $sensitiveTypes, true);
    }
}
