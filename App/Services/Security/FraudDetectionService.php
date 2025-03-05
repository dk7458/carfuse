<?php

namespace App\Services\Security;

use App\Helpers\ExceptionHandler;
use App\Helpers\ConfigHelper;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * FraudDetectionService - Centralized fraud detection system
 * 
 * This service is responsible for analyzing transactions to detect potential fraud
 * and calculating risk scores based on configurable fraud indicators.
 */
class FraudDetectionService
{
    /**
     * Default risk thresholds if not in config
     */
    private const DEFAULT_RISK_THRESHOLD_HIGH = 70;
    private const DEFAULT_RISK_THRESHOLD_MEDIUM = 50;
    private const DEFAULT_RISK_THRESHOLD_LOW = 30;
    
    /**
     * Default fraud indicator weights if not in config
     */
    private const DEFAULT_INDICATOR_WEIGHTS = [
        'high_amount' => 15,
        'multiple_attempts' => 20,
        'unusual_location' => 30,
        'address_mismatch' => 25,
        'card_country_mismatch' => 35,
        'rapid_transactions' => 18,
        'unusual_time' => 10,
        'ip_proxy_detected' => 40,
        'device_mismatch' => 28,
        'risky_email_domain' => 15
    ];
    
    /**
     * Default risky email domains if not in config
     */
    private const DEFAULT_RISKY_EMAIL_DOMAINS = [
        'tempmail.com', 'throwaway.com', 'mailinator.com',
        'guerrillamail.com', 'yopmail.com', 'sharklasers.com',
        'dispostable.com', '10minutemail.com', 'temp-mail.org'
    ];
    
    /**
     * Configuration cache
     */
    private array $config;
    private array $indicatorWeights;
    private array $riskThresholds;
    private array $riskyDomains;
    
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private string $requestId;
    
    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler = null,
        string $requestId = null,
        array $customConfig = null
    ) {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler ?? new ExceptionHandler($logger);
        $this->requestId = $requestId ?? uniqid('fraud_');
        
        // Load configuration
        $this->loadConfig($customConfig);
    }
    
    /**
     * Load fraud detection configuration
     */
    private function loadConfig(array $customConfig = null): void
    {
        try {
            // Use provided config or load from config file
            if ($customConfig !== null) {
                $this->config = $customConfig;
            } else {
                // Try to load from config file using ConfigHelper
                $this->config = ConfigHelper::get('fraud_detection') ?? [];
            }
            
            // Set risk thresholds from config or use defaults
            $this->riskThresholds = [
                'high' => $this->config['thresholds']['high'] ?? self::DEFAULT_RISK_THRESHOLD_HIGH,
                'medium' => $this->config['thresholds']['medium'] ?? self::DEFAULT_RISK_THRESHOLD_MEDIUM,
                'low' => $this->config['thresholds']['low'] ?? self::DEFAULT_RISK_THRESHOLD_LOW
            ];
            
            // Set indicator weights from config or use defaults
            $this->indicatorWeights = $this->config['indicator_weights'] ?? self::DEFAULT_INDICATOR_WEIGHTS;
            
            // Set risky email domains from config or use defaults
            $this->riskyDomains = $this->config['risky_email_domains'] ?? self::DEFAULT_RISKY_EMAIL_DOMAINS;
            
            $this->logger->debug("[FraudDetection] Configuration loaded", [
                'request_id' => $this->requestId,
                'custom_config' => $customConfig !== null ? 'yes' : 'no'
            ]);
        } catch (Exception $e) {
            $this->logger->error("[FraudDetection] Failed to load configuration: " . $e->getMessage(), [
                'request_id' => $this->requestId
            ]);
            // Use default values if config loading fails
            $this->riskThresholds = [
                'high' => self::DEFAULT_RISK_THRESHOLD_HIGH,
                'medium' => self::DEFAULT_RISK_THRESHOLD_MEDIUM,
                'low' => self::DEFAULT_RISK_THRESHOLD_LOW
            ];
            $this->indicatorWeights = self::DEFAULT_INDICATOR_WEIGHTS;
            $this->riskyDomains = self::DEFAULT_RISKY_EMAIL_DOMAINS;
        }
    }
    
    /**
     * Main entry point - analyze a transaction for fraud indicators
     * 
     * @param array $transactionData Complete transaction data
     * @param array $options Optional analysis options
     * @return array Analysis results with indicators, score and risk level
     */
    public function analyzeTransaction(array $transactionData, array $options = []): array
    {
        try {
            // Get time of analysis
            $analysisTime = time();
            
            // Detect fraud indicators
            $indicators = $this->detectFraudIndicators($transactionData, $options);
            
            // Calculate risk score based on indicators
            $riskScore = $this->calculateRiskScore($indicators);
            
            // Determine risk level based on score
            $riskLevel = $this->getRiskLevel($riskScore);
            
            // Determine appropriate log level
            $logLevel = $this->getLogLevelForRisk($riskScore);
            
            // Build recommendation based on risk level
            $recommendation = $this->generateRecommendation($riskLevel, $riskScore, $indicators);
            
            // Log high-risk transactions
            if ($riskScore >= $this->riskThresholds['medium']) {
                $this->logger->warning("[FraudDetection] High risk transaction detected", [
                    'request_id' => $this->requestId,
                    'transaction_id' => $transactionData['id'] ?? 'unknown',
                    'risk_score' => $riskScore,
                    'risk_level' => $riskLevel,
                    'indicators' => array_keys(array_filter($indicators))
                ]);
            }
            
            // Return complete analysis
            return [
                'indicators' => $indicators,
                'risk_score' => $riskScore,
                'risk_level' => $riskLevel,
                'log_level' => $logLevel,
                'recommendation' => $recommendation,
                'analysis_time' => $analysisTime,
                'request_id' => $this->requestId
            ];
        } catch (Exception $e) {
            $this->logger->error("[FraudDetection] Analysis failed: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'transaction' => isset($transactionData['id']) ? $transactionData['id'] : 'unknown'
            ]);
            $this->exceptionHandler->handleException($e);
            
            // Return safe default values
            return [
                'indicators' => [],
                'risk_score' => 0,
                'risk_level' => 'error',
                'log_level' => 'error',
                'recommendation' => 'review_manually',
                'error' => 'Analysis failed: ' . $e->getMessage(),
                'analysis_time' => time(),
                'request_id' => $this->requestId
            ];
        }
    }
    
    /**
     * Analyze transaction data to detect potential fraud indicators
     *
     * @param array $data Transaction data
     * @param array $options Optional detection options 
     * @return array Detected fraud indicators (indicator name => boolean)
     */
    public function detectFraudIndicators(array $data, array $options = []): array
    {
        $indicators = [];
        $rules = $this->config['rules'] ?? [];
        
        // Check for high amount transactions
        $highAmountThreshold = $rules['high_amount_threshold'] ?? 1000;
        if (isset($data['amount']) && $data['amount'] > $highAmountThreshold) {
            $indicators['high_amount'] = true;
        }
        
        // Check for multiple payment attempts
        $maxAttempts = $rules['max_payment_attempts'] ?? 3;
        if (isset($data['attempts']) && $data['attempts'] > $maxAttempts) {
            $indicators['multiple_attempts'] = true;
        }
        
        // Check for location mismatches
        if (isset($data['location'], $data['expected_location']) && 
            $data['location'] !== $data['expected_location']) {
            $indicators['unusual_location'] = true;
        }
        
        // Check for address mismatches between billing and shipping
        if (isset($data['billing_country'], $data['shipping_country']) && 
            $data['billing_country'] !== $data['shipping_country']) {
            $indicators['address_mismatch'] = true;
        }
        
        // Check for card country mismatch
        if (isset($data['card_country'], $data['user_country']) && 
            $data['card_country'] !== $data['user_country']) {
            $indicators['card_country_mismatch'] = true;
        }
        
        // Check for rapid transactions from the same user
        $minTransactionInterval = $rules['min_transaction_interval_minutes'] ?? 5;
        if (isset($data['last_transaction_minutes']) && 
            $data['last_transaction_minutes'] < $minTransactionInterval) {
            $indicators['rapid_transactions'] = true;
        }
        
        // Check for unusual transaction times
        $startBusinessHour = $rules['business_hours_start'] ?? 6;
        $endBusinessHour = $rules['business_hours_end'] ?? 23;
        if (isset($data['hour']) && ($data['hour'] < $startBusinessHour || $data['hour'] > $endBusinessHour)) {
            $indicators['unusual_time'] = true;
        }
        
        // Check for IP proxy usage
        if (isset($data['ip_is_proxy']) && $data['ip_is_proxy'] === true) {
            $indicators['ip_proxy_detected'] = true;
        }
        
        // Check for device mismatch with previous session
        if (isset($data['device_changed']) && $data['device_changed'] === true) {
            $indicators['device_mismatch'] = true;
        }
        
        // Check for risky email domains
        if (isset($data['email']) && $this->isRiskyEmailDomain($data['email'])) {
            $indicators['risky_email_domain'] = true;
        }
        
        // Run custom rules from config if defined
        if (isset($this->config['custom_rules']) && is_array($this->config['custom_rules'])) {
            foreach ($this->config['custom_rules'] as $ruleName => $ruleData) {
                if (isset($ruleData['condition']) && is_callable($ruleData['condition'])) {
                    try {
                        $result = call_user_func($ruleData['condition'], $data, $options);
                        if ($result === true) {
                            $indicators[$ruleName] = true;
                        }
                    } catch (Exception $e) {
                        $this->logger->warning("[FraudDetection] Custom rule failed: {$ruleName}", [
                            'request_id' => $this->requestId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        return $indicators;
    }
    
    /**
     * Calculate a risk score based on detected fraud indicators
     *
     * @param array $indicators Detected fraud indicators
     * @return int Risk score (0-100)
     */
    public function calculateRiskScore(array $indicators): int
    {
        $score = 0;
        
        foreach ($indicators as $key => $flag) {
            if ($flag && isset($this->indicatorWeights[$key])) {
                $score += $this->indicatorWeights[$key];
            }
        }
        
        // Cap the score at 100
        return min($score, 100);
    }
    
    /**
     * Get risk level description based on score
     *
     * @param int $score Risk score
     * @return string Risk level (high, medium, low, minimal)
     */
    public function getRiskLevel(int $score): string
    {
        if ($score >= $this->riskThresholds['high']) return 'high';
        if ($score >= $this->riskThresholds['medium']) return 'medium';
        if ($score >= $this->riskThresholds['low']) return 'low';
        return 'minimal';
    }
    
    /**
     * Get appropriate log level based on risk score
     *
     * @param int $score Risk score
     * @return string Log level (critical, error, warning, info)
     */
    public function getLogLevelForRisk(int $score): string
    {
        if ($score >= $this->riskThresholds['high']) return 'critical';
        if ($score >= $this->riskThresholds['medium']) return 'error';
        if ($score >= $this->riskThresholds['low']) return 'warning';
        return 'info';
    }
    
    /**
     * Generate recommendation based on risk assessment
     *
     * @param string $riskLevel Risk level
     * @param int $riskScore Risk score
     * @param array $indicators Detected indicators
     * @return string Recommendation code
     */
    public function generateRecommendation(string $riskLevel, int $riskScore, array $indicators): string
    {
        // Use configured recommendations or fallback to defaults
        $recommendations = $this->config['recommendations'] ?? [
            'high' => 'block_transaction',
            'medium' => 'additional_verification',
            'low' => 'flag_for_review',
            'minimal' => 'proceed'
        ];
        
        // Special cases based on specific indicators
        if (isset($indicators['ip_proxy_detected']) && $indicators['ip_proxy_detected']) {
            return 'additional_verification';
        }
        
        if (isset($indicators['card_country_mismatch']) && $indicators['card_country_mismatch']) {
            return $riskLevel === 'high' ? 'block_transaction' : 'additional_verification';
        }
        
        // Default recommendation based on risk level
        return $recommendations[$riskLevel] ?? 'review_manually';
    }
    
    /**
     * Check if an email domain is considered risky
     *
     * @param string $email Email address to check
     * @return bool True if the domain is risky
     */
    private function isRiskyEmailDomain(string $email): bool
    {
        $parts = explode('@', $email);
        if (count($parts) != 2) {
            return false;
        }
        
        $domain = strtolower($parts[1]);
        return in_array($domain, $this->riskyDomains);
    }
    
    /**
     * Get a copy of the current configuration
     *
     * @return array Current configuration
     */
    public function getConfig(): array
    {
        return [
            'risk_thresholds' => $this->riskThresholds,
            'indicator_weights' => $this->indicatorWeights,
            'risky_domains' => $this->riskyDomains,
            'rules' => $this->config['rules'] ?? []
        ];
    }
}
