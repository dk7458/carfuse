<?php

namespace App\Services\Audit;

use App\Services\Audit\LogManagementService;
use App\Services\Security\FraudDetectionService;
use App\Helpers\ExceptionHandler;
use Psr\Log\LoggerInterface;
use Exception;

class TransactionAuditService
{
    private LogManagementService $logManager;
    private FraudDetectionService $fraudDetector;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    
    public function __construct(
        LogManagementService $logManager, 
        FraudDetectionService $fraudDetector,
        LoggerInterface $logger = null,
        ExceptionHandler $exceptionHandler = null
    ) {
        $this->logManager = $logManager;
        $this->fraudDetector = $fraudDetector;
        $this->logger = $logger ?? $logManager->getLogger();
        $this->exceptionHandler = $exceptionHandler ?? new ExceptionHandler($this->logger);
    }
    
    /**
     * Generic transaction event logger
     */
    public function logEvent(
        string $category,
        string $message,
        array $context,
        ?int $userId = null,
        ?int $bookingId = null,
        string $logLevel = 'info'
    ): ?int {
        try {
            // Ensure we have transaction type if not specified
            if (!isset($context['transaction_type']) && !isset($context['event_type'])) {
                $context['transaction_type'] = $category;
            }
            
            return $this->logManager->createLogEntry($category, $message, $context, $userId, $bookingId, null, $logLevel);
        } catch (Exception $e) {
            $this->logger->error("[TransactionAudit] Failed to log event: " . $e->getMessage(), [
                'category' => $category,
                'user_id' => $userId,
                'booking_id' => $bookingId
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Record a successful payment transaction
     */
    public function recordPaymentSuccess(array $paymentData): ?int {
        try {
            $message = "Payment processed successfully";
            if (isset($paymentData['amount'])) {
                $message .= sprintf(" for %.2f %s", $paymentData['amount'], $paymentData['currency'] ?? '');
            }
            
            $paymentData['payment_status'] = 'completed';
            
            return $this->logEvent(
                'payment', 
                $message, 
                $paymentData, 
                $paymentData['user_id'] ?? null,
                $paymentData['booking_id'] ?? null, 
                'info'
            );
        } catch (Exception $e) {
            $this->logger->error("[TransactionAudit] Failed to record payment: " . $e->getMessage(), [
                'payment_id' => $paymentData['id'] ?? 'unknown'
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Record a fraud validation failure using FraudDetectionService
     */
    public function recordFraudValidationFailure(
        array $paymentData, 
        array $fraudIndicators = []
    ): ?int {
        try {
            // Use FraudDetectionService to analyze the transaction
            $fraudAnalysis = $this->fraudDetector->analyzeTransaction($paymentData);
            
            $message = sprintf(
                "Potential fraud detected (%s risk) with score %d", 
                $fraudAnalysis['risk_level'], 
                $fraudAnalysis['risk_score']
            );
            
            $context = array_merge($paymentData, [
                'fraud_indicators' => $fraudAnalysis['indicators'],
                'risk_score' => $fraudAnalysis['risk_score'],
                'risk_level' => $fraudAnalysis['risk_level'],
                'event_type' => 'fraud_attempt'
            ]);
            
            return $this->logEvent(
                'security', 
                $message, 
                $context,
                $paymentData['user_id'] ?? null,
                $paymentData['booking_id'] ?? null,
                $fraudAnalysis['log_level']
            );
        } catch (Exception $e) {
            $this->logger->error("[TransactionAudit] Failed to record fraud validation: " . $e->getMessage(), [
                'payment_id' => $paymentData['id'] ?? 'unknown'
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Record a transaction
     */
    public function recordTransaction(
        string $transactionType, 
        array $transactionData, 
        string $status, 
        ?string $message = null
    ): ?int {
        try {
            if ($message === null) {
                $message = ucfirst($transactionType) . " transaction " . $status;
                if (isset($transactionData['amount'])) {
                    $message .= sprintf(" for %.2f %s", $transactionData['amount'], $transactionData['currency'] ?? '');
                }
            }
            
            $context = array_merge($transactionData, [
                'transaction_type' => $transactionType,
                'status' => $status
            ]);
            
            // Define log level based on transaction status
            $logLevel = $this->getLogLevelForStatus($status);
            
            return $this->logEvent(
                'transaction', 
                $message, 
                $context,
                $transactionData['user_id'] ?? null,
                $transactionData['booking_id'] ?? null,
                $logLevel
            );
        } catch (Exception $e) {
            $this->logger->error("[TransactionAudit] Failed to record transaction: " . $e->getMessage(), [
                'transaction_type' => $transactionType,
                'status' => $status
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Record a refund event
     */
    public function recordRefund(array $refundData, string $status): ?int {
        try {
            $message = sprintf(
                "Refund %s for payment ID %s", 
                $status,
                $refundData['payment_id'] ?? 'unknown'
            );
            
            if (isset($refundData['amount'])) {
                $message .= sprintf(" (%.2f %s)", $refundData['amount'], $refundData['currency'] ?? '');
            }
            
            return $this->recordTransaction('refund', $refundData, $status, $message);
        } catch (Exception $e) {
            $this->logger->error("[TransactionAudit] Failed to record refund: " . $e->getMessage(), [
                'payment_id' => $refundData['payment_id'] ?? 'unknown',
                'status' => $status
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Get log level for transaction status
     */
    private function getLogLevelForStatus(string $status): string
    {
        $map = [
            'completed' => 'info', 'success' => 'info', 'pending' => 'info', 'processing' => 'info',
            'failed' => 'warning', 'declined' => 'warning', 'error' => 'error', 'fraud' => 'error',
            'cancelled' => 'info', 'refunded' => 'info'
        ];
        
        $s = strtolower($status);
        return $map[$s] ?? 'info';
    }
}
