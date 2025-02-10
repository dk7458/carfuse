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
            http_response_code(400);
            echo json_encode(['status' => 'error','message' => 'Validation failed','data' => $this->validator->errors()]);
            exit;
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

            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Payment processed','data' => ['transaction' => $transaction]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Payment processing failed', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Payment processing failed','data' => []]);
        }
        exit;
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
            http_response_code(400);
            echo json_encode(['status' => 'error','message' => 'Validation failed','data' => $this->validator->errors()]);
            exit;
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

            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Refund processed','data' => ['refund' => $refund]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Refund processing failed', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Refund processing failed','data' => []]);
        }
        exit;
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
            http_response_code(400);
            echo json_encode(['status' => 'error','message' => 'Validation failed','data' => $this->validator->errors()]);
            exit;
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

            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Installment plan created','data' => ['installment_plan' => $installmentPlan]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Installment plan setup failed', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Installment plan setup failed','data' => []]);
        }
        exit;
    }

    /**
     * Fetch all user transactions.
     */
    public function getUserTransactions(int $userId): array
    {
        try {
            $transactions = $this->paymentService->getUserTransactions($userId);
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Transactions fetched','data' => ['transactions' => $transactions]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to fetch user transactions', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to fetch user transactions','data' => []]);
        }
        exit;
    }

    /**
     * Fetch payment details.
     */
    public function getPaymentDetails(int $transactionId): array
    {
        try {
            $details = $this->paymentService->getPaymentDetails($transactionId);
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Payment details fetched','data' => ['details' => $details]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to fetch payment details', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to fetch payment details','data' => []]);
        }
        exit;
    }
}
