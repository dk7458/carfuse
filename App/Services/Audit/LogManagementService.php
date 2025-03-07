<?php

namespace App\Services\Audit;

use App\Helpers\DatabaseHelper;
use App\Helpers\SecurityHelper;
use App\Helpers\ExceptionHandler;
use App\Models\AuditLog;
use Psr\Log\LoggerInterface;
use Exception;

class LogManagementService
{
    private LoggerInterface $logger;
    private string $requestId;
    private ExceptionHandler $exceptionHandler;
    private array $config;
    private AuditLog $auditLog;
    
    public function __construct(
        LoggerInterface $logger, 
        string $requestId, 
        ExceptionHandler $exceptionHandler,
        AuditLog $auditLog = null
    ) {
        $this->logger = $logger;
        $this->requestId = $requestId;
        $this->exceptionHandler = $exceptionHandler;
        $this->config = require __DIR__ . '/../../../config/audit.php';
        $this->auditLog = $auditLog ?? new AuditLog();
    }
    
    /**
     * Get the logger instance
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
    
    /**
     * CENTRALIZED LOG INSERTION - All log writes go through this method
     */
    public function createLogEntry(
        string $category, 
        string $message, 
        array $context = [], 
        ?int $userId = null, 
        ?int $bookingId = null, 
        ?string $ipAddress = null,
        string $logLevel = 'info'
    ): ?int {
        try {
            // Check if logging is enabled for this level
            if (!($this->config['log_levels'][$logLevel] ?? true)) {
                return null;
            }
            
            // Sanitize inputs
            $message = SecurityHelper::sanitizeString($message);
            $context = $this->sanitizeContext($context);
            
            // Ensure request_id is included
            $context['request_id'] = $this->requestId;
            
            // Add client IP if not provided
            if (empty($ipAddress)) {
                $ipAddress = $this->getClientIp();
            }
            
            // Prepare data for insertion
            $data = [
                'action'             => $category,
                'message'            => $message,
                'details'            => $context,
                'user_reference'     => $userId,
                'booking_reference'  => $bookingId,
                'ip_address'         => $ipAddress,
                'created_at'         => date('Y-m-d H:i:s'),
                'log_level'          => $logLevel,
                'request_id'         => $this->requestId,
            ];
            
            // Use AuditLog model for consistent DB operations
            return $this->auditLog->createLog($data);
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to create log entry: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'category' => $category
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Get logs with filtering options - delegates to AuditLog model
     */
    public function getLogs(array $filters = []): array
    {
        try {
            return $this->auditLog->getLogs($filters);
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to get logs: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'filters' => $filters
            ]);
            $this->exceptionHandler->handleException($e);
            return ['data' => [], 'pagination' => ['total' => 0]];
        }
    }
    
    /**
     * Delete logs - delegates to AuditLog model
     */
    public function deleteLogs(array $filters, bool $forceBulkDelete = false): int
    {
        try {
            return $this->auditLog->deleteLogs($filters, $forceBulkDelete);
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to delete logs: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'filters' => $filters
            ]);
            $this->exceptionHandler->handleException($e);
            return 0;
        }
    }
    
    /**
     * Export logs - delegates to AuditLog model
     */
    public function exportLogs(array $filters): array
    {
        try {
            return $this->auditLog->exportLogs($filters);
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to export logs: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'filters' => $filters
            ]);
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
    
    /**
     * Get log by ID - delegates to AuditLog model
     */
    public function getLogById(int $logId): ?array
    {
        try {
            return $this->auditLog->getById($logId);
        } catch (Exception $e) {
            $this->logger->error("[LogManager] Failed to get log by ID: " . $e->getMessage(), [
                'request_id' => $this->requestId,
                'log_id' => $logId
            ]);
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Sanitize context array to prevent sensitive data storage
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'auth', 'key', 'apiKey', 'api_key', 
                         'credential', 'credit_card', 'card_number', 'cvv', 'ssn'];
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            if ($value === null) continue;
            
            $lower = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveKeys as $sKey) {
                if (strpos($lower, $sKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            $sanitized[$key] = $isSensitive 
                ? '[REDACTED]' 
                : (is_array($value) ? $this->sanitizeContext($value) : $value);
        }
        
        return $sanitized;
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
