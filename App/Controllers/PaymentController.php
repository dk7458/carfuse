<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use App\Models\InstallmentPlan;
use Illuminate\Support\Facades\Auth;

/**
 * Payment Controller
 *
 * Handles payment processing, refunds, installment payments, and user transactions.
 */
class PaymentController extends Controller
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
    public function processPayment(): void
    {
        $data = $this->validateRequest($_POST, [
            'user_id'          => 'required|integer',
            'booking_id'       => 'required|integer',
            'amount'           => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|integer',
        ]);
        try {
            $payment = Payment::create([
                'booking_id'     => $data['booking_id'],
                'user_id'        => $data['user_id'],
                'amount'         => $data['amount'],
                'payment_method' => $data['payment_method_id'],
                'status'         => 'completed'
            ]);
            // Update related booking status via Eloquent relationship
            $payment->booking()->update(['status' => 'paid']);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment processed',
                'data'    => ['payment' => $payment]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Payment processing failed',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(): void
    {
        $data = $this->validateRequest($_POST, [
            'transaction_id' => 'required|integer',
            'amount'         => 'required|numeric|min:0.01',
        ]);
        try {
            $refund = RefundLog::create([
                'transaction_id' => $data['transaction_id'],
                'amount'         => $data['amount'],
                'status'         => 'processed'
            ]);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Refund processed',
                'data'    => ['refund' => $refund]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Refund processing failed',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Set up installment payments.
     */
    public function setupInstallment(): void
    {
        $data = $this->validateRequest($_POST, [
            'user_id'           => 'required|integer',
            'total_amount'      => 'required|numeric|min:0.01',
            'installments'      => 'required|integer|min:2',
            'payment_method_id' => 'required|integer',
        ]);
        try {
            $plan = InstallmentPlan::create([
                'user_id'        => $data['user_id'],
                'total_amount'   => $data['total_amount'],
                'installments'   => $data['installments'],
                'payment_method' => $data['payment_method_id'],
            ]);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Installment plan created',
                'data'    => ['installment_plan' => $plan]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Installment plan setup failed',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Fetch all user transactions.
     */
    public function getUserTransactions(): void
    {
        try {
            $transactions = TransactionLog::with(['payment', 'booking'])
                ->where('user_id', $_SESSION['user_id'] ?? null)
                ->latest()
                ->get();
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Transactions fetched',
                'data'    => ['transactions' => $transactions]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch user transactions',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Fetch payment details.
     */
    public function getPaymentDetails(int $transactionId): void
    {
        try {
            $details = TransactionLog::findOrFail($transactionId);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment details fetched',
                'data'    => ['details' => $details]
            ], 200);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch payment details',
                'data'    => []
            ], 500);
        }
    }
}
