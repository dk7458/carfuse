<?php

namespace App\Services;

use App\Services\Payment\PaymentProcessingService;
use App\Services\Payment\RefundService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\TransactionService;

class PaymentService
{
    /**
     * PaymentService acts as a facade:
     *  - Delegates payment processing to PaymentProcessingService
     *  - Delegates refund handling to RefundService
     *  - Delegates external gateway calls to PaymentGatewayService
     *  - Delegates transaction logging/history to TransactionService
     *
     * Controllers (and other parts of your codebase) continue to call PaymentService
     * without knowing about the underlying subservices, avoiding any breaking changes.
     */

    private PaymentProcessingService $paymentProcessingService;
    private RefundService $refundService;
    private PaymentGatewayService $paymentGatewayService;
    private TransactionService $transactionService;

    /**
     * Constructor injects the four subservices, which are then used to delegate
     * the responsibilities away from this facade class.
     */
    public function __construct(
        PaymentProcessingService $paymentProcessingService,
        RefundService $refundService,
        PaymentGatewayService $paymentGatewayService,
        TransactionService $transactionService
    ) {
        $this->paymentProcessingService = $paymentProcessingService;
        $this->refundService = $refundService;
        $this->paymentGatewayService = $paymentGatewayService;
        $this->transactionService = $transactionService;
    }

    /**
     * Wrapper for handling a payment. Delegates the core logic to PaymentProcessingService.
     *
     * @param array $paymentData
     * @return array
     */
    public function processPayment(array $paymentData): array
    {
        return $this->paymentProcessingService->processPayment($paymentData);
    }

    /**
     * Wrapper for handling a refund. Delegates the core logic to RefundService.
     *
     * @param array $refundData
     * @return array
     */
    public function refundPayment(array $refundData): array
    {
        return $this->refundService->refund($refundData);
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
     * For logging transactions directly through PaymentService, if needed.
     *
     * @param array $transactionData
     * @return array
     */
    public function logTransaction(array $transactionData): array
    {
        return $this->transactionService->logTransaction($transactionData);
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
}
