<?php

namespace App\Services\Audit;

use App\Helpers\ExceptionHandler;
use App\Models\AuditLog;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Exception;

class UserAuditService
{
    private LogManagementService $logManager;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $logger;
    private array $config;
    
    /**
     * User event categories
     */
    private const CATEGORY_AUTH = 'auth';
    private const CATEGORY_USER = 'user';
    private const CATEGORY_SYSTEM = 'system';
    private const CATEGORY_SECURITY = 'security';
    private const CATEGORY_API = 'api';
    
    public function __construct(
        LogManagementService $logManager, 
        ExceptionHandler $exceptionHandler,
        LoggerInterface $logger = null
    ) {
        $this->logManager = $logManager;
        $this->exceptionHandler = $exceptionHandler;
        $this->logger = $logger ?? $logManager->getLogger() ?? new NullLogger();
        $this->config = require __DIR__ . '/../../../config/audit.php';
    }
    
    /**
     * Main method for logging any user-related event
     * All other logging methods route through this one
     *
     * @param string $category Event category
     * @param string $action Specific action being performed
     * @param string $message Log message
     * @param array $context Additional context data
     * @param int|null $userId Associated user ID
     * @param string $logLevel Log level
     * @return int|null Log entry ID if successful
     */
    public function logUserEvent(
        string $category,
        string $action,
        string $message,
        array $context = [],
        ?int $userId = null,
        string $logLevel = 'info'
    ): ?int {
        try {
            // Check if this log level is enabled in config
            if (!($this->config['log_levels'][$logLevel] ?? true)) {
                return null;
            }
            
            // Enrich context with user-specific data
            $enrichedContext = $this->enrichUserContext($action, $context);
            
            // Route through LogManagementService
            return $this->logManager->createLogEntry(
                $category, 
                $message, 
                $enrichedContext, 
                $userId, 
                null, 
                null, 
                $logLevel
            );
        } catch (Exception $e) {
            $this->logger->error("[UserAudit] Failed to log user event: " . $e->getMessage(), [
                'category' => $category,
                'action' => $action,
                'user_id' => $userId
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Log an authentication event
     */
    public function logAuthEvent(
        string $action,
        string $message,
        array $context = [],
        ?int $userId = null,
        string $logLevel = 'info'
    ): ?int {
        return $this->logUserEvent(self::CATEGORY_AUTH, $action, $message, $context, $userId, $logLevel);
    }
    
    /**
     * Log a user action
     */
    public function logUserAction(
        string $action,
        string $message,
        array $context = [],
        ?int $userId = null,
        string $logLevel = 'info'
    ): ?int {
        return $this->logUserEvent(self::CATEGORY_USER, $action, $message, $context, $userId, $logLevel);
    }
    
    /**
     * Log a system event
     */
    public function logSystemEvent(
        string $action,
        string $message,
        array $context = [],
        ?int $userId = null,
        string $logLevel = 'info'
    ): ?int {
        return $this->logUserEvent(self::CATEGORY_SYSTEM, $action, $message, $context, $userId, $logLevel);
    }
    
    /**
     * Log a security event
     */
    public function logSecurityEvent(
        string $action,
        string $message,
        array $context = [],
        ?int $userId = null,
        string $logLevel = 'warning'
    ): ?int {
        return $this->logUserEvent(self::CATEGORY_SECURITY, $action, $message, $context, $userId, $logLevel);
    }
    
    /**
     * Log an API request
     */
    public function logApiRequest(
        string $endpoint,
        string $method,
        string $message,
        array $context = [],
        ?int $userId = null,
        string $logLevel = 'info'
    ): ?int {
        $apiContext = array_merge($context, [
            'endpoint' => $endpoint,
            'method' => $method
        ]);
        
        return $this->logUserEvent(self::CATEGORY_API, 'api_request', $message, $apiContext, $userId, $logLevel);
    }
    
    /**
     * Enrich context with user-specific data
     */
    private function enrichUserContext(string $action, array $context): array
    {
        $enrichedContext = array_merge($context, [
            'action' => $action,
            'user_agent' => $context['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        
        // Add IP address if not already in context
        if (!isset($enrichedContext['ip_address'])) {
            $enrichedContext['ip_address'] = $this->getClientIp();
        }
        
        // Add timestamp if not already in context
        if (!isset($enrichedContext['timestamp'])) {
            $enrichedContext['timestamp'] = date('Y-m-d H:i:s');
        }
        
        return $enrichedContext;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP'] as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
