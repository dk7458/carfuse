<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use App\Models\InstallmentPlan;
use App\Services\AuditService;
use App\Helpers\TokenValidator;
use App\Helpers\ExceptionHandler;
use App\Helpers\DatabaseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

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
    private ResponseFactoryInterface $responseFactory;
    protected LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        PaymentService $paymentService,
        Validator $validator,
        NotificationService $notificationService,
        AuditService $auditService,
        ResponseFactoryInterface $responseFactory,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger);
        $this->paymentService = $paymentService;
        $this->validator = $validator;
        $this->notificationService = $notificationService;
        $this->auditService = $auditService;
        $this->responseFactory = $responseFactory;
        $this->exceptionHandler = $exceptionHandler;
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
        try {
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
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Payment processing failed'
            ], 500);
        }
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(): ResponseInterface
    {
        try {
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
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Refund processing failed'
            ], 500);
        }
    }

    /**
     * Set up installment payments.
     */
    public function setupInstallment(): ResponseInterface
    {
        try {
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
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Installment plan setup failed'
            ], 500);
        }
    }

    /**
     * Fetch all user transactions.
     */
    public function getUserTransactions(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
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
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch user transactions'
            ], 500);
        }
    }

    /**
     * Fetch payment details.
     */
    public function getPaymentDetails(int $transactionId): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
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
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch payment details'
            ], 500);
        }
    }

    /**
     * Process payment for an installment.
     */
    public function processInstallmentPayment(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            $data = $this->validateRequest($_POST, [
                'installment_id'    => 'required|integer',
                'payment_method_id' => 'required|integer',
            ]);
            
            // Get the installment details using DatabaseHelper
            $installment = DatabaseHelper::select(
                "SELECT * FROM installments WHERE id = ? AND user_id = ?",
                [(int)$data['installment_id'], $user->id],
                false // Using application database
            );
            
            if (empty($installment)) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Installment not found or does not belong to you'
                ], 404);
            }
            
            $installmentData = $installment[0];
            
            // Process payment for this specific installment
            $payment = Payment::create([
                'user_id'        => $user->id,
                'booking_id'     => $installmentData['booking_id'],
                'installment_id' => $data['installment_id'],
                'amount'         => $installmentData['amount'],
                'payment_method' => $data['payment_method_id'],
                'status'         => 'completed',
            ]);
            
            // Update installment status
            DatabaseHelper::update(
                "installments",
                ["status" => "paid", "paid_at" => date('Y-m-d H:i:s')],
                ["id" => (int)$data['installment_id']],
                false // Using application database
            );
            
            // Log the payment in audit logs
            $this->auditService->logEvent(
                'installment_payment_processed',
                "Installment payment processed",
                [
                    'user_id' => $user->id,
                    'installment_id' => $data['installment_id'],
                    'payment_id' => $payment->id,
                    'amount' => $installmentData['amount']
                ],
                $user->id,
                $installmentData['booking_id'],
                'payment'
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Installment payment processed successfully',
                'data'    => ['payment' => $payment]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to process installment payment'
            ], 500);
        }
    }

    /**
     * Add a payment method.
     */
    public function addPaymentMethod(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            $data = $this->validateRequest($_POST, [
                'type'        => 'required|string',
                'card_last4'  => 'required_if:type,credit_card|numeric',
                'card_brand'  => 'required_if:type,credit_card|string',
                'expiry_date' => 'required_if:type,credit_card|string',
                'is_default'  => 'nullable|boolean',
            ]);
            
            // Add payment method using DatabaseHelper for secure storage
            $paymentMethodId = DatabaseHelper::insert(
                "payment_methods",
                [
                    "user_id" => $user->id,
                    "type" => $data['type'],
                    "card_last4" => $data['card_last4'] ?? null,
                    "card_brand" => $data['card_brand'] ?? null,
                    "expiry_date" => $data['expiry_date'] ?? null,
                    "is_default" => $data['is_default'] ?? false,
                    "created_at" => date('Y-m-d H:i:s')
                ],
                true // Using secure database for payment details
            );
            
            if (!$paymentMethodId) {
                throw new \RuntimeException("Failed to add payment method");
            }
            
            // If this is set as default, update other methods to non-default
            if (!empty($data['is_default']) && $data['is_default']) {
                DatabaseHelper::execute(
                    "UPDATE payment_methods SET is_default = 0 WHERE user_id = ? AND id != ?",
                    [$user->id, $paymentMethodId],
                    true // Using secure database
                );
            }
            
            // Get the newly created payment method
            $paymentMethod = DatabaseHelper::select(
                "SELECT id, type, card_brand, card_last4, expiry_date, is_default 
                 FROM payment_methods 
                 WHERE id = ?",
                [$paymentMethodId],
                true // Using secure database
            )[0];
            
            // Log in audit logs
            $this->auditService->logEvent(
                'payment_method_added',
                "User added a payment method",
                [
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethodId,
                    'type' => $data['type']
                ],
                $user->id,
                null,
                'payment'
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment method added successfully',
                'data'    => ['payment_method' => $paymentMethod]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to add payment method'
            ], 500);
        }
    }
    
    /**
     * Get all payment methods for a user.
     */
    public function getUserPaymentMethods(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Fetch payment methods using DatabaseHelper from secure database
            $paymentMethods = DatabaseHelper::select(
                "SELECT id, type, card_brand, card_last4, expiry_date, is_default 
                 FROM payment_methods 
                 WHERE user_id = ? AND deleted_at IS NULL",
                [$user->id],
                true // Using secure database
            );
            
            // Log in audit logs
            $this->auditService->logEvent(
                'payment_methods_viewed',
                "User viewed their payment methods",
                ['user_id' => $user->id],
                $user->id,
                null,
                'payment'
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment methods retrieved successfully',
                'data'    => ['payment_methods' => $paymentMethods]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to retrieve payment methods'
            ], 500);
        }
    }
}
