<?php

namespace App\Services\Payment;

use App\Helpers\DatabaseHelper;
use App\Models\Payment;
use App\Models\TransactionLog;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Exception;

class RefundService
{
    /**
     * Handles all refund-related operations.
     * 
     * Responsibilities:
     *  - Securely process refund requests
     *  - Verify that original payment exists and is refundable
     *  - Log refund transactions in TransactionLog
     *  - Use AuditService for approved refunds and chargebacks
     *  - Use LoggerInterface for refund API failures and debugging
     */
    private DatabaseHelper $dbHelper;
    private Payment $paymentModel;
    private TransactionLog $transactionLogModel;
    private AuditService $auditService;
    private LoggerInterface $logger;

    /**
     * Constructor injects all necessary dependencies for refund operations.
     */
    public function __construct(
        DatabaseHelper $dbHelper,
        Payment $paymentModel,
        TransactionLog $transactionLogModel,
        AuditService $auditService,
        LoggerInterface $logger
    ) {
        $this->dbHelper = $dbHelper;
        $this->paymentModel = $paymentModel;
        $this->transactionLogModel = $transactionLogModel;
        $this->auditService = $auditService;
        $this->logger = $logger;
    }

    /**
     * Main method for processing a refund.
     *
     * @param array $refundData
     *   Example structure: [
     *       'payment_id'  => 123,
     *       'amount'      => 100.00,
     *       'reason'      => 'Product defective',
     *       'initiated_by'=> 'admin' or 'customer',
     *       // Other relevant data...
     *   ]
     * @return array
     *   Return a standardized response, e.g. ['status' => 'success', 'refund_id' => XYZ]
     * @throws Exception
     *   When refund cannot be processed
     */
    public function refund(array $refundData): array
    {
        // Check if refund data is valid
        if (!$this->isValidRefundData($refundData)) {
            $this->logger->error('Invalid refund data provided', $refundData);
            throw new Exception('Refund data is invalid.');
        }

        // Fetch and verify original payment
        $originalPayment = $this->paymentModel->findPayment($refundData['payment_id']);
        if (!$originalPayment) {
            $this->logger->warning('No original payment found for refund', $refundData);
            throw new Exception('Original payment not found.');
        }

        // Check if the payment is eligible for refund
        if (!$this->isRefundable($originalPayment, $refundData)) {
            $this->logger->warning('Payment not eligible for refund', [
                'originalPayment' => $originalPayment,
                'refundRequest'   => $refundData
            ]);
            throw new Exception('Payment is not eligible for a refund.');
        }

        // Begin transaction
        $this->dbHelper->beginTransaction();
        try {
            // Create a refund record using the Payment model's method
            $refundId = $this->paymentModel->createRefund($refundData);

            // Log the refund in the transaction log
            $this->transactionLogModel->logTransaction([
                'payment_id'    => $refundData['payment_id'],
                'refund_id'     => $refundId,
                'amount'        => $refundData['amount'],
                'status'        => 'refunded',
                'description'   => "Refund processed: {$refundData['reason']}",
            ]);

            // Commit transaction
            $this->dbHelper->commit();

            // Audit the approved refund
            $this->auditService->recordRefundSuccess($refundData);

            // Return success response
            return [
                'status' => 'success',
                'refund_id' => $refundId,
                'message' => 'Refund processed successfully.'
            ];
        } catch (Exception $e) {
            // Rollback transaction
            $this->dbHelper->rollback();

            // Log error
            $this->logger->error('Refund processing failed', [
                'error' => $e->getMessage(),
                'refundData' => $refundData
            ]);

            throw $e;
        }
    }

    /**
     * Validate minimal refund data.
     */
    private function isValidRefundData(array $refundData): bool
    {
        return (!empty($refundData['payment_id']) && !empty($refundData['amount']));
    }

    /**
     * Checks whether the original payment is eligible for a refund.
     * This can involve checking payment status, refund policies, etc.
     *
     * @param array $originalPayment Payment record from the DB
     * @param array $refundData
     * @return bool
     */
    private function isRefundable(array $originalPayment, array $refundData): bool
    {
        // Example checks:
        //  - Payment must be 'completed'
        //  - Refund amount <= original payment amount
        //  - Payment is within refundable time window, etc.
        if ($originalPayment['status'] !== 'completed') {
            return false;
        }

        if ($refundData['amount'] > $originalPayment['amount']) {
            return false;
        }

        // More sophisticated checks can go here
        return true;
    }
}
