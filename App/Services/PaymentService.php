<?php

namespace App\Services;

use App\Services\Payment\PaymentProcessingService;
use App\Services\Payment\RefundService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\TransactionService;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\TransactionLog;
use App\Models\Booking;

class PaymentService
{
    /**
     * PaymentService acts as a facade:
     *  - Delegates payment processing to PaymentProcessingService
     *  - Delegates refund handling to RefundService
     *  - Delegates external gateway calls to PaymentGatewayService
     *  - Delegates transaction logging/history to TransactionService
     */

    private PaymentProcessingService $paymentProcessingService;
    private RefundService $refundService;
    private PaymentGatewayService $paymentGatewayService;
    private TransactionService $transactionService;
    private Payment $paymentModel;
    private PaymentMethod $paymentMethodModel;
    private Booking $bookingModel;
    private AuditService $auditService;

    /**
     * Constructor injects the subservices and models
     */
    public function __construct(
        PaymentProcessingService $paymentProcessingService,
        RefundService $refundService,
        PaymentGatewayService $paymentGatewayService,
        TransactionService $transactionService,
        Payment $paymentModel,
        PaymentMethod $paymentMethodModel,
        Booking $bookingModel,
        AuditService $auditService
    ) {
        $this->paymentProcessingService = $paymentProcessingService;
        $this->refundService = $refundService;
        $this->paymentGatewayService = $paymentGatewayService;
        $this->transactionService = $transactionService;
        $this->paymentModel = $paymentModel;
        $this->paymentMethodModel = $paymentMethodModel;
        $this->bookingModel = $bookingModel;
        $this->auditService = $auditService;
    }

    /**
     * Process a payment
     *
     * @param array $paymentData Required fields: booking_id, amount, payment_method_id, user_id
     * @return array Payment details
     */
    public function processPayment(array $paymentData): array
    {
        // Map payment method ID to an actual payment method name
        $paymentMethod = $this->paymentMethodModel->getById($paymentData['payment_method_id']);
        if (!$paymentMethod) {
            throw new \Exception('Invalid payment method');
        }
        
        // Prepare formatted payment data 
        $formattedPaymentData = [
            'booking_id'     => $paymentData['booking_id'],
            'user_id'        => $paymentData['user_id'],
            'amount'         => $paymentData['amount'],
            'method'         => $paymentMethod['payment_type'] ?? 'credit_card',
            'payment_method' => $paymentData['payment_method_id'],
            'status'         => 'pending', // Initial status
            'currency'       => $paymentData['currency'] ?? 'USD',
        ];

        // Delegate to payment processing service
        $result = $this->paymentProcessingService->processPayment($formattedPaymentData);
        
        // After payment processed, make sure booking is updated
        if ($result['status'] === 'success' && isset($result['payment_id'])) {
            $this->bookingModel->updateStatus($paymentData['booking_id'], 'paid');
            
            // Record the successful payment in audit logs
            $this->auditService->logEvent(
                'payment_processed',
                "Payment of {$paymentData['amount']} processed for booking #{$paymentData['booking_id']}",
                [
                    'payment_id' => $result['payment_id'],
                    'booking_id' => $paymentData['booking_id'],
                    'user_id'    => $paymentData['user_id'],
                    'amount'     => $paymentData['amount']
                ],
                $paymentData['user_id'],
                $paymentData['booking_id'],
                'payment'
            );
        }
        
        return $result;
    }

    /**
     * Handle payment refund
     *
     * @param array $refundData Required fields: payment_id, amount, reason, admin_id
     * @return array Refund details
     */
    public function refundPayment(array $refundData): array
    {
        // Get the original payment
        $payment = $this->paymentModel->find($refundData['payment_id']);
        if (!$payment) {
            throw new \Exception('Original payment not found');
        }
        
        // Prepare refund data
        $formattedRefundData = [
            'payment_id'         => $refundData['payment_id'],
            'original_payment_id'=> $refundData['payment_id'],
            'amount'             => $refundData['amount'],
            'reason'             => $refundData['reason'],
            'initiated_by'       => 'admin',
            'admin_id'           => $refundData['admin_id'],
            'user_id'            => $payment['user_id'],
            'booking_id'         => $payment['booking_id'],
            'method'             => $payment['method']
        ];
        
        // Delegate to refund service
        $result = $this->refundService->refund($formattedRefundData);
        
        return $result;
    }
    
    /**
     * Add a payment method
     * 
     * @param array $methodData Required fields: type, user_id
     * @return array Payment method details
     */
    public function addPaymentMethod(array $methodData): array
    {
        // Use the payment method model to add the method
        $methodId = $this->paymentMethodModel->createPaymentMethodWithValidation([
            'user_id'     => $methodData['user_id'],
            'payment_type'=> $methodData['type'],
            'card_last4'  => $methodData['card_last4'] ?? null,
            'card_brand'  => $methodData['card_brand'] ?? null,
            'expiry_date' => $methodData['expiry_date'] ?? null,
            'is_active'   => true,
            'is_default'  => $methodData['is_default'] ?? false,
        ]);
        
        if (!$methodId) {
            throw new \Exception('Failed to add payment method');
        }
        
        // If set as default, update other methods
        if (!empty($methodData['is_default']) && $methodData['is_default']) {
            $this->paymentMethodModel->setDefaultMethod($methodData['user_id'], $methodId);
        }
        
        // Audit log
        $this->auditService->logEvent(
            'payment_method_added',
            "User added a payment method",
            [
                'user_id'          => $methodData['user_id'],
                'payment_method_id'=> $methodId,
                'type'             => $methodData['type']
            ],
            $methodData['user_id'],
            null,
            'payment'
        );
        
        // Return the created payment method
        return $this->paymentMethodModel->getById($methodId);
    }
    
    /**
     * Get payment methods for a user
     * 
     * @param int $userId
     * @return array List of payment methods
     */
    public function getUserPaymentMethods(int $userId): array
    {
        // Simply delegate to the model
        $methods = $this->paymentMethodModel->getByUser($userId);
        
        // Audit log
        $this->auditService->logEvent(
            'payment_methods_viewed',
            "User viewed their payment methods",
            ['user_id' => $userId],
            $userId,
            null,
            'payment'
        );
        
        return $methods;
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
        // Delegate to transaction service for this read operation
        return $this->transactionService->getTransactionDetails($transactionId, $userId, $isAdmin);
    }

    /**
     * For handling gateway callback/webhook data. Delegates to PaymentGatewayService.
     *
     * @param string $gatewayName
     * @param array  $callbackData
     * @return array
     */
    public function handlePaymentCallback(string $gatewayName, array $callbackData): array
    {
        return $this->paymentGatewayService->handleCallback($gatewayName, $callbackData);
    }

    /**
     * Retrieves transaction history for a specific user.
     *
     * @param int $userId
     * @return array
     */
    public function getTransactionHistory(int $userId): array
    {
        return $this->transactionService->getHistoryByUser($userId);
    }

    /**
     * Retrieves transaction history with admin filters (date range, type, etc.).
     *
     * @param array $filters
     * @return array
     */
    public function getTransactionHistoryAdmin(array $filters): array
    {
        return $this->transactionService->getHistoryAdmin($filters);
    }
    
    /**
     * For direct interactions with external gateways (e.g., if you need to manually
     * initiate a gateway payment step or retrieve gateway-specific responses).
     *
     * @param string $gatewayName  E.g. "stripe", "payu", etc.
     * @param array  $paymentData  Payment details to pass to the gateway
     * @return array
     */
    public function processPaymentGateway(string $gatewayName, array $paymentData): array
    {
        return $this->paymentGatewayService->processPayment($gatewayName, $paymentData);
    }
}
