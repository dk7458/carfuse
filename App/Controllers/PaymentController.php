<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use App\Models\InstallmentPlan;
use App\Services\AuditService;
use App\Helpers\TokenValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

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
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        PaymentService $paymentService,
        Validator $validator,
        NotificationService $notificationService,
        AuditService $auditService,
        PDO $db,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->paymentService = $paymentService;
        $this->validator = $validator;
        $this->notificationService = $notificationService;
        $this->auditService = $auditService;
        $this->db = $db;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Create standardized PSR-7 JSON response
     */
    protected function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Process a payment.
     */
    public function processPayment(): ResponseInterface
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }
        
        $data = $this->validateRequest($_POST, [
            'booking_id'       => 'required|integer',
            'amount'           => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|integer',
        ]);
        
        try {
            $payment = Payment::create([
                'booking_id'     => $data['booking_id'],
                'user_id'        => $user->id,
                'amount'         => $data['amount'],
                'payment_method' => $data['payment_method_id'],
                'status'         => 'completed'
            ]);
            
            // Update related booking status via Eloquent relationship
            $payment->booking()->update(['status' => 'paid']);
            
            // Log the payment in the secure audit logs
            $this->auditService->logEvent(
                'payment_processed',
                "Payment of {$data['amount']} processed for booking #{$data['booking_id']}",
                [
                    'payment_id' => $payment->id,
                    'booking_id' => $data['booking_id'],
                    'user_id' => $user->id,
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method_id']
                ],
                $user->id,
                $data['booking_id'],
                'payment'
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment processed',
                'data'    => ['payment' => $payment]
            ]);
        } catch (\Exception $e) {
            // Log the error
            $this->auditService->logEvent(
                'payment_failed',
                "Payment processing failed for booking #{$data['booking_id']}",
                [
                    'booking_id' => $data['booking_id'],
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ],
                $user->id,
                $data['booking_id'],
                'payment',
                'error'
            );
            
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Payment processing failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(): ResponseInterface
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Unauthorized - admin rights required'
            ], 401);
        }
        
        $data = $this->validateRequest($_POST, [
            'transaction_id' => 'required|integer',
            'amount'         => 'required|numeric|min:0.01',
        ]);
        
        try {
            // Get the original transaction
            $transaction = TransactionLog::findOrFail($data['transaction_id']);
            
            $refund = RefundLog::create([
                'transaction_id' => $data['transaction_id'],
                'amount'         => $data['amount'],
                'status'         => 'processed'
            ]);
            
            // Log the refund in the secure audit logs
            $this->auditService->logEvent(
                'refund_processed',
                "Refund of {$data['amount']} processed for transaction #{$data['transaction_id']}",
                [
                    'refund_id' => $refund->id,
                    'transaction_id' => $data['transaction_id'],
                    'booking_id' => $transaction->booking_id,
                    'user_id' => $transaction->user_id,
                    'admin_id' => $admin->id,
                    'amount' => $data['amount']
                ],
                $admin->id,
                $transaction->booking_id,
                'payment'
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Refund processed',
                'data'    => ['refund' => $refund]
            ]);
        } catch (\Exception $e) {
            // Log the error
            $this->auditService->logEvent(
                'refund_failed',
                "Refund processing failed for transaction #{$data['transaction_id']}",
                [
                    'transaction_id' => $data['transaction_id'],
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage()
                ],
                $admin->id,
                null,
                'payment',
                'error'
            );
            
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Refund processing failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set up installment payments.
     */
    public function setupInstallment(): ResponseInterface
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }
        
        $data = $this->validateRequest($_POST, [
            'total_amount'      => 'required|numeric|min:0.01',
            'installments'      => 'required|integer|min:2',
            'payment_method_id' => 'required|integer',
            'booking_id'        => 'required|integer',
        ]);
        
        try {
            $plan = InstallmentPlan::create([
                'user_id'        => $user->id,
                'booking_id'     => $data['booking_id'],
                'total_amount'   => $data['total_amount'],
                'installments'   => $data['installments'],
                'payment_method' => $data['payment_method_id'],
            ]);
            
            // Log the installment plan creation
            $this->auditService->logEvent(
                'installment_plan_created',
                "Installment plan created for user #{$user->id} with {$data['installments']} installments",
                [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'booking_id' => $data['booking_id'],
                    'total_amount' => $data['total_amount'],
                    'installments' => $data['installments']
                ],
                $user->id,
                $data['booking_id'],
                'payment'
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Installment plan created',
                'data'    => ['installment_plan' => $plan]
            ]);
        } catch (\Exception $e) {
            // Log the error
            $this->auditService->logEvent(
                'installment_plan_failed',
                "Installment plan creation failed for user #{$user->id}",
                [
                    'user_id' => $user->id,
                    'booking_id' => $data['booking_id'] ?? null,
                    'error' => $e->getMessage()
                ],
                $user->id,
                $data['booking_id'] ?? null,
                'payment',
                'error'
            );
            
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Installment plan setup failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch all user transactions.
     */
    public function getUserTransactions(): ResponseInterface
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }
        
        try {
            $transactions = TransactionLog::with(['payment', 'booking'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();
            
            // Log the transaction view activity
            $this->auditService->logEvent(
                'transactions_viewed',
                "User viewed their transaction history",
                ['user_id' => $user->id],
                $user->id,
                null,
                'payment'
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Transactions fetched',
                'data'    => ['transactions' => $transactions]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch user transactions',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch payment details.
     */
    public function getPaymentDetails(int $transactionId): ResponseInterface
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }
        
        try {
            $details = TransactionLog::findOrFail($transactionId);
            
            // Verify the user owns this transaction or is an admin
            if ($details->user_id != $user->id && !$user->isAdmin()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this transaction'
                ], 403);
            }
            
            // Log the access to payment details
            $this->auditService->logEvent(
                'payment_details_viewed',
                "Payment details accessed for transaction #{$transactionId}",
                [
                    'transaction_id' => $transactionId,
                    'user_id' => $user->id,
                    'is_admin' => $user->isAdmin() ? 'yes' : 'no'
                ],
                $user->id,
                $details->booking_id ?? null,
                'payment'
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment details fetched',
                'data'    => ['details' => $details]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch payment details',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
