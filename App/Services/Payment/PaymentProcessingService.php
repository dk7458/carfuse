<?php

namespace App\Services\Payment;

use App\Helpers\DatabaseHelper;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\TransactionLog;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Exception;

class PaymentProcessingService
{
    /**
     * Handles payment initiation, validation, database transactions,
     * and logging for successful or failed payments.
     *
     * @author 
     * @version 1.0
     * @description
     *   - PaymentValidation & Payment Initiation
     *   - Transaction handling (beginTransaction, commit/rollback)
     *   - Stores payment record in Payment model
     *   - Updates booking status in Booking model
     *   - Logs transaction in TransactionLog model
     *   - Uses AuditService for successful payment and fraud validation
     *   - Uses LoggerInterface for API failures and debugging
     */
    private DatabaseHelper $dbHelper;
    private Payment $paymentModel;
    private Booking $bookingModel;
    private TransactionLog $transactionLogModel;
    private AuditService $auditService;
    private LoggerInterface $logger;

    /**
     * Constructor injects all necessary dependencies for payment processing.
     */
    public function __construct(
        DatabaseHelper $dbHelper,
        Payment $paymentModel,
        Booking $bookingModel,
        TransactionLog $transactionLogModel,
        AuditService $auditService,
        LoggerInterface $logger
    ) {
        $this->dbHelper = $dbHelper;
        $this->paymentModel = $paymentModel;
        $this->bookingModel = $bookingModel;
        $this->transactionLogModel = $transactionLogModel;
        $this->auditService = $auditService;
        $this->logger = $logger;
    }

    /**
     * Main method for processing a payment.
     * 
     * @param array $paymentData
     *   Example structure: [
     *       'booking_id' => 123,
     *       'amount'     => 500.00,
     *       'currency'   => 'USD',
     *       'payment_method' => 'stripe',
     *       'customer_id' => 456,
     *       // Other relevant data...
     *   ]
     * @return array
     *   Return a standardized response, e.g. ['status' => 'success', 'payment_id' => XYZ]
     * @throws Exception
     *   In case of transaction failure or invalid data
     */
    public function processPayment(array $paymentData): array
    {
        // Perform comprehensive fraud validation before processing
        $fraudCheckResult = $this->performFraudValidation($paymentData);
        if (!$fraudCheckResult['valid']) {
            $this->logger->error('Payment rejected: Potential fraud detected', [
                'payment_data' => $paymentData,
                'fraud_indicators' => $fraudCheckResult['indicators']
            ]);
            
            // Log the fraud attempt with detailed information for investigation
            $this->auditService->logEvent(
                'security', 
                'fraud_attempt', 
                [
                    'payment_data' => $paymentData,
                    'fraud_indicators' => $fraudCheckResult['indicators'],
                    'risk_score' => $fraudCheckResult['risk_score'] ?? null,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'timestamp' => date('Y-m-d H:i:s')
                ],
                $paymentData['user_id'] ?? null,
                $paymentData['booking_id'] ?? null
            );
            
            throw new Exception('Payment rejected due to fraud indicators: ' . 
                implode(', ', $fraudCheckResult['indicators']));
        }

        // Start DB transaction
        $this->dbHelper->beginTransaction();

        try {
            // 1. Create Payment record
            $paymentId = $this->paymentModel->createPayment($paymentData);

            // 2. Update Booking status using the updateStatus method
            $this->bookingModel->updateStatus($paymentData['booking_id'], 'paid');

            // 3. Insert Transaction Log entry
            $this->transactionLogModel->logTransaction([
                'payment_id' => $paymentId,
                'booking_id' => $paymentData['booking_id'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'status' => 'completed',
                'description' => 'Payment processed successfully.',
            ]);

            // 4. Commit transaction
            $this->dbHelper->commit();

            // 5. Audit successful payment with enhanced audit data
            $auditPaymentData = array_merge($paymentData, [
                'payment_id' => $paymentId,
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'completed',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            
            $this->auditService->recordPaymentSuccess($auditPaymentData);

            // Return success response
            return [
                'status' => 'success',
                'payment_id' => $paymentId,
                'message' => 'Payment processed successfully.'
            ];
        } catch (Exception $e) {
            // Roll back on any failure
            $this->dbHelper->rollback();

            // Log the error
            $this->logger->error('Payment processing failed', [
                'error' => $e->getMessage(),
                'data'  => $paymentData
            ]);

            // Rethrow or handle exception
            throw $e;
        }
    }

    /**
     * Perform comprehensive fraud validation on payment data
     * 
     * @param array $paymentData
     * @return array Result with validation status and fraud indicators
     */
    private function performFraudValidation(array $paymentData): array
    {
        $fraudIndicators = [];
        $riskScore = 0;
        
        // 1. Required field validation
        if (empty($paymentData['booking_id'])) {
            $fraudIndicators[] = 'missing_booking_id';
            $riskScore += 25;
        }
        
        if (empty($paymentData['amount']) || !is_numeric($paymentData['amount']) || $paymentData['amount'] <= 0) {
            $fraudIndicators[] = 'invalid_amount';
            $riskScore += 25;
        }
        
        if (empty($paymentData['currency'])) {
            $fraudIndicators[] = 'missing_currency';
            $riskScore += 15;
        }
        
        // 2. Velocity checks - too many transactions in short period
        if (!empty($paymentData['user_id'])) {
            $recentTransactions = $this->getRecentUserTransactions(
                $paymentData['user_id'], 
                15  // Look back 15 minutes
            );
            
            if (count($recentTransactions) > 5) {
                $fraudIndicators[] = 'transaction_velocity';
                $riskScore += 30;
            }
        }
        
        // 3. Amount threshold checks
        if (!empty($paymentData['amount'])) {
            // Unusually large payment
            if ($paymentData['amount'] > 10000) {
                $fraudIndicators[] = 'large_amount';
                $riskScore += 20;
            }
            
            // Round amount (often used in testing fraud)
            if ($paymentData['amount'] == round($paymentData['amount'])) {
                $fraudIndicators[] = 'round_amount';
                $riskScore += 5;
            }
        }
        
        // 4. IP address reputation check (pseudocode - implement with actual service)
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ipReputation = $this->checkIpReputation($_SERVER['REMOTE_ADDR']);
            if ($ipReputation === 'suspicious') {
                $fraudIndicators[] = 'suspicious_ip';
                $riskScore += 40;
            } elseif ($ipReputation === 'high_risk') {
                $fraudIndicators[] = 'high_risk_ip';
                $riskScore += 70;
            }
        }
        
        // 5. Check if user account is flagged for suspicious activity
        if (!empty($paymentData['user_id'])) {
            $userFlags = $this->getUserFlags($paymentData['user_id']);
            if (in_array('suspicious_activity', $userFlags)) {
                $fraudIndicators[] = 'flagged_account';
                $riskScore += 50;
            }
        }
        
        // Determine validation result based on risk score and critical indicators
        $isValid = $riskScore < 50 && count($fraudIndicators) < 2;
        
        // Log for troubleshooting/tuning
        if ($riskScore > 30) {
            $this->logger->info('Elevated risk score in payment', [
                'risk_score' => $riskScore,
                'indicators' => $fraudIndicators,
                'user_id' => $paymentData['user_id'] ?? null
            ]);
        }
        
        return [
            'valid' => $isValid,
            'indicators' => $fraudIndicators,
            'risk_score' => $riskScore
        ];
    }
    
    /**
     * Get recent transactions from a specific user
     * 
     * @param int $userId User ID
     * @param int $minutesBack Minutes to look back
     * @return array Recent transactions
     */
    private function getRecentUserTransactions(int $userId, int $minutesBack = 15): array
    {
        $timestamp = date('Y-m-d H:i:s', time() - ($minutesBack * 60));
        
        $query = "SELECT * FROM transaction_logs 
                 WHERE user_id = :user_id 
                 AND created_at > :timestamp";
                 
        return $this->dbHelper->select($query, [
            ':user_id' => $userId,
            ':timestamp' => $timestamp
        ]);
    }
    
    /**
     * Check IP address reputation
     * 
     * @param string $ipAddress IP address to check
     * @return string Reputation score (safe, suspicious, high_risk)
     */
    private function checkIpReputation(string $ipAddress): string
    {
        // In a production environment, this would call an IP reputation service
        // such as MaxMind, IPQualityScore, or similar
        
        // For now, return safe for most IPs, with some randomized suspicious ones
        if (substr($ipAddress, 0, 3) === '10.' || substr($ipAddress, 0, 4) === '192.') {
            return 'safe'; // Most internal IPs are safe
        }
        
        // Very basic sample implementation - replace with actual service
        $ipHash = crc32($ipAddress);
        if ($ipHash % 100 < 3) {
            return 'high_risk';
        } elseif ($ipHash % 100 < 10) {
            return 'suspicious';
        }
        
        return 'safe';
    }
    
    /**
     * Get user account flags from security system
     * 
     * @param int $userId User ID
     * @return array List of user flags
     */
    private function getUserFlags(int $userId): array
    {
        // In production, fetch from user_security_flags table or similar
        // For now, we'll return empty array for most users
        
        // Example implementation - check if user has had chargebacks
        $query = "SELECT COUNT(*) as count FROM transaction_logs 
                 WHERE user_id = :user_id AND type = 'chargeback'";
                 
        $result = $this->dbHelper->select($query, [':user_id' => $userId]);
        if (isset($result[0]['count']) && $result[0]['count'] > 0) {
            return ['chargeback_history'];
        }
        
        return [];
    }

    /**
     * Basic check to ensure payment data meets minimal criteria.
     * This could be extended for additional fraud checks or data validation.
     * @deprecated Use performFraudValidation instead
     */
    private function isValidPaymentData(array $data): bool
    {
        if (empty($data['booking_id']) || empty($data['amount']) || empty($data['currency'])) {
            return false;
        }
        return true;
    }
}
