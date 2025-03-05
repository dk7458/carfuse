<?php

namespace App\Services;

use Exception;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use App\Helpers\DatabaseHelper;
use App\Helpers\LogLevelFilter;
use App\Helpers\ExceptionHandler;
use App\Helpers\SecurityHelper;
use App\Services\Audit\LogManagementService;
use App\Services\Audit\UserAuditService;
use App\Services\Audit\TransactionAuditService;
use Psr\Log\LoggerInterface;

class AuditService
{
    // Categories and log levels remain unchanged
    public const CATEGORY_SYSTEM       = 'system';
    public const CATEGORY_AUTH         = 'auth';
    public const CATEGORY_TRANSACTION  = 'transaction';
    public const CATEGORY_BOOKING      = 'booking';
    public const CATEGORY_USER         = 'user';
    public const CATEGORY_ADMIN        = 'admin';
    public const CATEGORY_DOCUMENT     = 'document';
    public const CATEGORY_API          = 'api';
    public const CATEGORY_SECURITY     = 'security';
    public const CATEGORY_PAYMENT      = 'payment';
    
    public const LOG_LEVEL_DEBUG       = 'debug';
    public const LOG_LEVEL_INFO        = 'info';
    public const LOG_LEVEL_WARNING     = 'warning';
    public const LOG_LEVEL_ERROR       = 'error';
    public const LOG_LEVEL_CRITICAL    = 'critical';
    
    private const VALID_CATEGORIES = [
        self::CATEGORY_SYSTEM, self::CATEGORY_AUTH, self::CATEGORY_TRANSACTION,
        self::CATEGORY_BOOKING, self::CATEGORY_USER, self::CATEGORY_ADMIN,
        self::CATEGORY_DOCUMENT, self::CATEGORY_API, self::CATEGORY_SECURITY,
        self::CATEGORY_PAYMENT,
    ];
    
    // Define which categories should be stored in the audit database
    private const AUDIT_CATEGORIES = [
        self::CATEGORY_AUTH, 
        self::CATEGORY_SECURITY, 
        self::CATEGORY_PAYMENT,
        self::CATEGORY_TRANSACTION,
        self::CATEGORY_BOOKING,
        self::CATEGORY_USER,
        self::CATEGORY_ADMIN
    ];
    
    private const CATEGORY_MAP = [
        self::CATEGORY_AUTH       => 'user',
        self::CATEGORY_USER       => 'user',
        self::CATEGORY_SYSTEM     => 'user',
        self::CATEGORY_API        => 'user',
        self::CATEGORY_SECURITY   => 'user', 
        self::CATEGORY_TRANSACTION=> 'transaction',
        self::CATEGORY_PAYMENT    => 'transaction'
    ];
    
    // Configuration constants
    public const DEBUG_MODE = true;
    
    // Injected services
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private LogLevelFilter $logLevelFilter;
    private string $requestId;
    
    // Injected subservices
    private LogManagementService $logManager;
    private UserAuditService $userAuditService;
    private TransactionAuditService $transactionAuditService;

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        LogManagementService $logManager,
        UserAuditService $userAuditService,
        TransactionAuditService $transactionAuditService,
        LogLevelFilter $logLevelFilter = null
    ) {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->logManager = $logManager;
        $this->userAuditService = $userAuditService;
        $this->transactionAuditService = $transactionAuditService;
        $this->logLevelFilter = $logLevelFilter ?? new LogLevelFilter();
        $this->requestId = Uuid::uuid4()->toString();
        
        if (self::DEBUG_MODE) {
            $this->logger->debug("[Audit] Service initialized", [
                'request_id' => $this->requestId
            ]);
        }
    }
    
    /**
     * Get the request ID for this instance
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }
    
    /**
     * Main entry point to log an event.
     * Routes events either to general logger or audit database based on category.
     */
    public function logEvent(
        string $category, 
        string $message, 
        array $context = [], 
        ?int $userId = null, 
        ?int $bookingId = null, 
        ?string $ipAddress = null,
        string $logLevel = self::LOG_LEVEL_INFO
    ): ?int {
        if (!$this->logLevelFilter->shouldLog($logLevel)) {
            return null;
        }
        
        try {
            $normalizedCat = $this->normalizeCategory($category);
            $context = $this->sanitizeContext($context);
            $context['request_id'] = $this->requestId;
            
            // For system events unrelated to security/payments/auth, use general logger only
            if (!in_array($normalizedCat, self::AUDIT_CATEGORIES, true)) {
                $logMethod = strtolower($logLevel);
                $this->logger->$logMethod("[{$normalizedCat}] {$message}", $context);
                return null;
            }
            
            // Route to appropriate subservice based on category
            $serviceKey = self::CATEGORY_MAP[$normalizedCat] ?? null;
            
            if ($serviceKey === 'user') {
                // Use UserAuditService for user-related events
                return $this->userAuditService->logUserEvent(
                    $normalizedCat, 
                    $context['action'] ?? $normalizedCat,
                    $message, 
                    $context,
                    $userId, 
                    $logLevel
                );
            }
            
            if ($serviceKey === 'transaction') {
                // Use TransactionAuditService for transaction-related events
                return $this->transactionAuditService->logEvent(
                    $normalizedCat, 
                    $message, 
                    $context, 
                    $userId, 
                    $bookingId, 
                    $logLevel
                );
            }
            
            // Default path for audit events that don't have specialized handlers
            return $this->logManager->createLogEntry(
                $normalizedCat,
                $message,
                $context,
                $userId,
                $bookingId,
                $ipAddress,
                $logLevel
            );
            
        } catch (Exception $e) {
            $this->logger->error("[Audit] Logging failed: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Normalize and validate a category
     */
    private function normalizeCategory(string $category): string {
        $normalizedCat = strtolower(trim($category));
        if (!in_array($normalizedCat, self::VALID_CATEGORIES, true)) {
            $normalizedCat = self::CATEGORY_SYSTEM;
            if (self::DEBUG_MODE) {
                $this->logger->warning("[Audit] Invalid category", [
                    'request_id' => $this->requestId,
                    'invalid' => $category,
                    'default' => $normalizedCat
                ]);
            }
        }
        return $normalizedCat;
    }
    
    /**
     * Legacy method - remains for backward compatibility
     */
    public function recordEvent(
        string $category,
        string $action,
        string $message,
        array $context = [],
        ?int $userId = null,
        ?int $objectId = null,
        string $logLevel = self::LOG_LEVEL_INFO
    ): ?int {
        $eventContext = array_merge($context, [
            'action' => $action,
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'object_id' => $objectId
        ]);
        return $this->logEvent(
            $category,
            $message,
            $eventContext,
            $userId,
            $category === self::CATEGORY_BOOKING ? $objectId : null,
            null,
            $logLevel
        );
    }
    
    /**
     * Record a successful payment
     */
    public function recordPaymentSuccess(array $paymentData): ?int {
        return $this->transactionAuditService->recordPaymentSuccess($paymentData);
    }
    
    /**
     * Log an authentication event
     */
    public function logAuthEvent(string $action, string $message, array $context = [], ?int $userId = null, string $logLevel = self::LOG_LEVEL_INFO): ?int {
        return $this->userAuditService->logAuthEvent($action, $message, $context, $userId, $logLevel);
    }
    
    // Delegate log management operations to LogManagementService
    public function getLogs(array $filters = []): array {
        return $this->logManager->getLogs($filters);
    }
    
    public function deleteLogs(array $filters, bool $forceBulkDelete = false): int {
        return $this->logManager->deleteLogs($filters, $forceBulkDelete);
    }
    
    public function exportLogs(array $filters): array {
        return $this->logManager->exportLogs($filters);
    }
    
    public function getLogById(int $logId): ?array {
        return $this->logManager->getLogById($logId);
    }
    
    /**
     * Helper method to sanitize context arrays
     */
    private function sanitizeContext(array $context): array {
        $sensitiveKeys = ['password', 'secret', 'token', 'auth', 'key', 'apiKey', 'api_key', 'credential', 'credit_card', 'card_number', 'cvv', 'ssn'];
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            if ($value === null) {
                continue;
            }
            
            $keyLower = strtolower($key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strpos($keyLower, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}
