<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Services\Validator;
use App\Services\NotificationService;
use AuditManager\Services\AuditService;
use PDO;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

/**
 * Payment Controller
 *
 * Handles payment processing, refunds, installment payments, and user transactions.
 */
class PaymentController
{
    private PaymentService $paymentService;
    private Validator $validator;
    private NotificationService $notificationService;
    private AuditService $auditService;
    private PDO $db;
    private LoggerInterface $logger;

    public function __construct(
        PaymentService $paymentService,
        Validator $validator,
        NotificationService $notificationService,
        AuditService $auditService,
        PDO $db,
        LoggerInterface $logger
    ) {
        $this->paymentService = $paymentService;
        $this->validator = $validator;
        $this->notificationService = $notificationService;
        $this->auditService = $auditService;
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Process a payment.
     */
    public function processPayment(array $data): array
    {
        $rules = [
            'user_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|integer',
        ];

        if (!$this->validator->validate($data, $rules)) {
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $transaction = $this->paymentService->processPayment(
                $data['user_id'],
                $data['payment_method_id'],
                $data['amount']
            );

            $this->auditService->log(
                'payment_processed',
                'Payment successfully processed.',
                $data['user_id'],
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            $this->notificationService->sendNotification(
                $data['user_id'],
                'email',
                "Payment of {$data['amount']} was successfully processed.",
                ['email' => $transaction['email']]
            );

            return ['status' => 'success', 'transaction' => $transaction];
        } catch (\Exception $e) {
            $this->logger->error('Payment processing failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Payment processing failed'];
        }
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(array $data): array
    {
        $rules = [
            'transaction_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
        ];

        if (!$this->validator->validate($data, $rules)) {
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $refund = $this->paymentService->processRefund(
                $data['transaction_id'],
                $data['amount']
            );

            $this->auditService->log(
                'refund_processed',
                'Refund successfully processed.',
                null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            $this->notificationService->sendNotification(
                $refund['user_id'],
                'email',
                "A refund of {$data['amount']} was processed for your transaction.",
                ['email' => $refund['email']]
            );

            return ['status' => 'success', 'refund' => $refund];
        } catch (\Exception $e) {
            $this->logger->error('Refund processing failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Refund processing failed'];
        }
    }

    /**
     * Set up installment payments.
     */
    public function setupInstallment(array $data): array
    {
        $rules = [
            'user_id' => 'required|integer',
            'total_amount' => 'required|numeric|min:0.01',
            'installments' => 'required|integer|min:2',
            'payment_method_id' => 'required|integer',
        ];

        if (!$this->validator->validate($data, $rules)) {
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $installmentPlan = $this->paymentService->createInstallmentPlan(
                $data['user_id'],
                $data['total_amount'],
                $data['installments'],
                $data['payment_method_id']
            );

            $this->auditService->log(
                'installment_plan_created',
                'Installment plan successfully created.',
                $data['user_id'],
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            $this->notificationService->sendNotification(
                $data['user_id'],
                'email',
                "Your installment plan for {$data['total_amount']} has been set up successfully.",
                ['email' => $installmentPlan['email']]
            );

            return ['status' => 'success', 'installment_plan' => $installmentPlan];
        } catch (\Exception $e) {
            $this->logger->error('Installment plan setup failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Installment plan setup failed'];
        }
    }

    /**
     * Fetch all user transactions.
     */
    public function getUserTransactions(int $userId): array
    {
        try {
            $transactions = $this->paymentService->getUserTransactions($userId);
            return ['status' => 'success', 'transactions' => $transactions];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user transactions', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to fetch user transactions'];
        }
    }

    /**
     * Fetch payment details.
     */
    public function getPaymentDetails(int $transactionId): array
    {
        try {
            $details = $this->paymentService->getPaymentDetails($transactionId);
            return ['status' => 'success', 'details' => $details];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch payment details', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to fetch payment details'];
        }
    }
}
