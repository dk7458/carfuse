<?php

namespace App;

use App\Services\Security\FraudDetectionService;
use App\Services\Audit\LogManagementService;
use App\Services\Audit\UserAuditService;
use App\Services\Audit\TransactionAuditService;
use App\Services\AuditService;
use App\Helpers\LogLevelFilter;
use App\Helpers\ExceptionHandler;
use App\Models\AuditLog;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Service Registry - Central place for creating and retrieving services
 */
class ServiceRegistry
{
    private static $services = [];
    private static $config = null;
    
    /**
     * Get the audit configuration
     */
    public static function getConfig(): array
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/audit.php';
        }
        return self::$config;
    }
    
    /**
     * Get AuditService instance
     */
    public static function getAuditService(LoggerInterface $logger, ExceptionHandler $exceptionHandler): AuditService
    {
        $key = 'audit_service';
        if (!isset(self::$services[$key])) {
            $requestId = Uuid::uuid4()->toString();
            
            // Create models
            $auditLog = new AuditLog();
            
            // Create helpers
            $logLevelFilter = new LogLevelFilter(self::getConfig()['log_levels']);
            
            // Create management services
            $logManager = new LogManagementService(
                $logger, 
                $requestId, 
                $exceptionHandler, 
                $auditLog
            );
            
            // Create security services
            $fraudDetector = new FraudDetectionService($logger, $exceptionHandler);
            
            // Create audit subservices
            $userAuditService = new UserAuditService($logManager, $exceptionHandler, $logger);
            $transactionAuditService = new TransactionAuditService(
                $logManager,
                $fraudDetector,
                $exceptionHandler,
                $logger
            );
            
            // Create main audit service
            self::$services[$key] = new AuditService(
                $logger,
                $exceptionHandler,
                $logManager,
                $userAuditService,
                $transactionAuditService,
                $logLevelFilter,
                $auditLog
            );
        }
        
        return self::$services[$key];
    }
}
