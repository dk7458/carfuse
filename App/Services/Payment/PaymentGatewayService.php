<?php

namespace App\Services\Payment;

use App\Models\TransactionLog;
use App\Models\Payment;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Exception;

class PaymentGatewayService
{
    /**
     * Handles external gateway payments
     */
    private LoggerInterface $logger;
    private TransactionLog $transactionLogModel;
    private Payment $paymentModel;
    private AuditService $auditService;
    private array $gatewayConfigs;

    /**
     * Constructor with required dependencies
     */
    public function __construct(
        LoggerInterface $logger, 
        TransactionLog $transactionLogModel,
        Payment $paymentModel,
        AuditService $auditService
    ) {
        $this->logger = $logger;
        $this->transactionLogModel = $transactionLogModel;
        $this->paymentModel = $paymentModel;
        $this->auditService = $auditService;
        
        // Load gateway configurations
        $this->gatewayConfigs = [
            'stripe' => [
                'api_key' => $_ENV['STRIPE_API_KEY'] ?? null,
                'webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null,
            ],
            'payu' => [
                'merchant_id' => $_ENV['PAYU_MERCHANT_ID'] ?? null,
                'api_key' => $_ENV['PAYU_API_KEY'] ?? null,
            ]
        ];
    }

    /**
     * Process payment with the specified gateway
     *
     * @param string $gatewayName
     * @param array $paymentData
     * @return array
     */
    public function processPayment(string $gatewayName, array $paymentData): array
    {
        try {
            // Validate basic payment data
            if (!isset($paymentData['amount']) || !isset($paymentData['user_id'])) {
                throw new Exception("Missing required payment data");
            }
            
            // Make sure we have gateway configuration
            $gatewayName = strtolower($gatewayName);
            if (!isset($this->gatewayConfigs[$gatewayName])) {
                throw new Exception("Unsupported payment gateway: {$gatewayName}");
            }
            
            // Process payment based on gateway
            switch ($gatewayName) {
                case 'stripe':
                    $result = $this->processStripePayment($paymentData);
                    break;
                case 'payu':
                    $result = $this->processPayUPayment($paymentData);
                    break;
                default:
                    throw new Exception("Gateway implementation not found: {$gatewayName}");
            }
            
            // Create payment record if successful
            if ($result['status'] === 'success') {
                $paymentId = $this->paymentModel->createPayment([
                    'user_id' => $paymentData['user_id'],
                    'booking_id' => $paymentData['booking_id'] ?? null,
                    'amount' => $paymentData['amount'],
                    'method' => $gatewayName,
                    'status' => 'completed',
                    'transaction_id' => $result['gateway_id'] ?? null,
                    'currency' => $paymentData['currency'] ?? 'USD',
                ]);
                
                // Log transaction
                $this->transactionLogModel->logTransaction([
                    'payment_id' => $paymentId,
                    'booking_id' => $paymentData['booking_id'] ?? null,
                    'user_id' => $paymentData['user_id'],
                    'amount' => $paymentData['amount'],
                    'currency' => $paymentData['currency'] ?? 'USD',
                    'status' => 'completed',
                    'type' => 'payment',
                    'description' => "Payment processed via {$gatewayName}",
                    'gateway' => $gatewayName,
                    'gateway_transaction_id' => $result['gateway_id'] ?? null,
                ]);
                
                // Add payment ID to result
                $result['payment_id'] = $paymentId;
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Payment gateway error', [
                'gateway' => $gatewayName,
                'data' => $paymentData,
                'error' => $e->getMessage()
            ]);
            
            $this->auditService->logEvent(
                'payment_gateway',
                'payment_failed',
                [
                    'gateway' => $gatewayName,
                    'error' => $e->getMessage(),
                    'user_id' => $paymentData['user_id'] ?? null,
                    'booking_id' => $paymentData['booking_id'] ?? null,
                    'amount' => $paymentData['amount'] ?? null
                ],
                $paymentData['user_id'] ?? null,
                $paymentData['booking_id'] ?? null
            );
            
            throw $e;
        }
    }

    /**
     * Handle gateway callback/webhook
     *
     * @param string $gatewayName
     * @param array $callbackData
     * @return array
     */
    public function handleCallback(string $gatewayName, array $callbackData): array
    {
        try {
            $gatewayName = strtolower($gatewayName);
            
            if (!isset($this->gatewayConfigs[$gatewayName])) {
                throw new Exception("Unsupported payment gateway callback: {$gatewayName}");
            }
            
            switch ($gatewayName) {
                case 'stripe':
                    return $this->handleStripeCallback($callbackData);
                case 'payu':
                    return $this->handlePayUCallback($callbackData);
                default:
                    throw new Exception("Gateway callback implementation not found: {$gatewayName}");
            }
        } catch (Exception $e) {
            $this->logger->error('Payment gateway callback error', [
                'gateway' => $gatewayName,
                'data' => $callbackData,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process Stripe payment
     */
    private function processStripePayment(array $paymentData): array
    {
        // In a real implementation, this would use the Stripe SDK
        $this->logger->info('Processing Stripe payment', $paymentData);

        // Let's simulate a successful payment
        return [
            'status' => 'success',
            'gateway_id' => 'stripe_' . bin2hex(random_bytes(8)),
            'message' => 'Stripe payment completed'
        ];
    }

    /**
     * Process PayU payment
     */
    private function processPayUPayment(array $paymentData): array
    {
        // In a real implementation, this would use the PayU API
        $this->logger->info('Processing PayU payment', $paymentData);
        
        // Let's simulate a successful payment
        return [
            'status' => 'success',
            'gateway_id' => 'payu_' . bin2hex(random_bytes(8)),
            'message' => 'PayU payment completed'
        ];
    }

    /**
     * Handle Stripe webhook callback
     */
    private function handleStripeCallback(array $callbackData): array
    {
        $this->logger->info('Processing Stripe callback', $callbackData);
        
        // In a real implementation, this would:
        // 1. Verify the webhook signature using Stripe SDK
        // 2. Extract payment details and status
        // 3. Update corresponding payment record
        // 4. Log transaction update
        
        // Let's simulate processing a webhook
        if (isset($callbackData['type']) && $callbackData['type'] === 'payment_intent.succeeded') {
            // Update payment and transaction in database
            
            // Audit the event
            $this->auditService->logEvent(
                'payment_gateway',
                'webhook_processed',
                [
                    'gateway' => 'stripe',
                    'event_type' => $callbackData['type'],
                    'payment_intent_id' => $callbackData['data']['object']['id'] ?? null
                ]
            );
        }
        
        return [
            'status' => 'received',
            'message' => 'Stripe webhook processed'
        ];
    }

    /**
     * Handle PayU webhook callback
     */
    private function handlePayUCallback(array $callbackData): array
    {
        $this->logger->info('Processing PayU callback', $callbackData);
        
        // Similar to Stripe callback handling, with PayU-specific logic
        
        return [
            'status' => 'received',
            'message' => 'PayU webhook processed'
        ];
    }
}
