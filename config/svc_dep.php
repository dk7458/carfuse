<?php

use DI\Container;
use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\LogLevelFilter;
use App\Services\Validator;
use App\Services\RateLimiter;
use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Services\PaymentService;
use App\Services\BookingService;
use App\Services\MetricsService;
use App\Services\ReportService;
use App\Services\RevenueService;
use App\Services\EncryptionService;
use App\Services\Security\KeyManager;
use App\Services\DocumentService;
use App\Services\FileStorage;
use App\Services\TemplateService;
use App\Services\SignatureService;
use App\Services\AuditService;
use App\Services\Payment\TransactionService;
use App\Services\PayUService;
use App\Models\User;
use App\Models\RefreshToken;
use App\Models\Admin;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Signature;
use App\Models\Booking;
use App\Models\TransactionLog;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\PaymentMethod;
use App\Models\DocumentQueue;
use App\Models\Notification;
use App\Services\AdminService;
use App\Services\Payment\PaymentProcessingService;
use App\Services\Payment\RefundService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Security\FraudDetectionService;
use App\Services\Audit\LogManagementService;
use App\Services\Audit\UserAuditService;
use App\Services\Audit\TransactionAuditService;
use App\Models\AuditLog;

global $logger, $loggers;  // ✅ Ensure global loggers are available


return function (Container $container, array $config) {
    // Make sure we have access to the global loggers
    global $logger, $loggers;

    // Register main application logger
    $container->set(LoggerInterface::class, function () use ($logger) {
        return $logger;  // Use the pre-initialized main application logger
    });
    
    // Register category-specific loggers from the global $loggers array
    foreach ($loggers as $category => $categoryLogger) {
        $container->set("logger.{$category}", function() use ($categoryLogger) {
            return $categoryLogger;
        });
    }
    
    // Register Models
    $container->set(User::class, function($c) {
        return new User(
            $c->get(DatabaseHelper::class),
            $c->get('logger.api') ?? $c->get('logger.db') ?? $c->get(LoggerInterface::class),  // Use specific logger if available
            $c->get(AuditService::class)
        );
    });

    $container->set(RefreshToken::class, function($c) {
        return new RefreshToken(
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class), 
            $c->get('logger.auth') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(Booking::class, function($c) {
        return new Booking(
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class),
            $c->get('logger.booking') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(Admin::class, function($c) {
        return new Admin(
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class),
            $c->get('logger.admin') ?? $c->get('logger.db') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(Payment::class, function($c) {
        return new Payment(
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(Signature::class, function($c) {
        return new Signature(
            $c->get(DatabaseHelper::class),
            $c->get(EncryptionService::class),
            $c->get(AuditService::class),
            $c->get('logger.security') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(TransactionLog::class, function($c) {
        return new TransactionLog(
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(Notification::class, function($c) {
        return new Notification(
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class),
            $c->get('logger.notification') ?? $c->get('logger.db') ?? $c->get(LoggerInterface::class)
        );
    });

    // Document related models
    $container->set(Document::class, function($c) {
        return new Document(
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class),
            $c->get('logger.document') ?? $c->get('logger.db') ?? $c->get(LoggerInterface::class)
        );
    });
    
    $container->set(DocumentTemplate::class, function($c) {
        return new DocumentTemplate(
            $c->get(DatabaseHelper::class),
            $c->get('logger.document') ?? $c->get('logger.db'),
            $c->get(AuditService::class)
        );
    });
    
    $container->set(Contract::class, function($c) {
        return new Contract(
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class),
            $c->get('logger.document') ?? $c->get('logger.db')
        );
    });

    // Update DocumentQueue to use injected config
    $container->set(DocumentQueue::class, function($c) use ($config) {
        return new DocumentQueue(
            $c->get('logger.document') ?? $c->get('logger.api'),
            $c->get(FileStorage::class),
            $config
        );
    });

    // Basic services with minimal dependencies
    $container->set(Validator::class, function($c) {
        return new Validator(
            $c->get('logger.api') ?? $c->get(LoggerInterface::class),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(RateLimiter::class, function($c) {
        return new RateLimiter(
            $c->get('logger.api') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(PaymentGatewayService::class, function($c) {
        return new PaymentGatewayService(
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class),
            $c->get(TransactionLog::class),
            $c->get(Payment::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(EncryptionService::class, function($c) use ($config) {
        return new EncryptionService(
            $c->get('logger.security') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $config['encryption']['key']
        );
    });

    // Skip re-registering core services that were initialized in bootstrap
    // The following services are already registered:
    // - LogLevelFilter
    // - FraudDetectionService
    // - LogManagementService
    // - UserAuditService
    // - TransactionAuditService
    // - AuditService

    // Services with dependencies on basic services
    $container->set(FileStorage::class, function($c) use ($config) {
        return new FileStorage(
            $config['storage'] ?? [],
            $c->get(EncryptionService::class),
            $c->get('logger.file') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(TokenService::class, function($c) use ($config) {
        return new TokenService(
            $config['encryption'],
            $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get('db'),
            $c->get(AuditService::class), // Using the pre-initialized AuditService
            $c->get(RefreshToken::class),
            $c->get(User::class)
        );
    });

    $container->set(TransactionService::class, function($c) {
        return new TransactionService(
            $c->get(TransactionLog::class),
            $c->get(Payment::class),
            $c->get(AuditService::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class)
        );
    });

    // Update existing service registrations
    $container->set(UserService::class, function($c) {
        return new UserService(
            $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class),
            $c->get(User::class),
            $config['encryption']['jwtSecret'] ?? '',
        );
    });

    $container->set(NotificationService::class, function($c) {
        return new NotificationService(
            $c->get('logger.notification') ?? $c->get('logger.api') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(Notification::class),
            $config['database'] ?? [],

        );
    });

    $container->set(DocumentService::class, function($c) use ($config) {
        return new DocumentService(
            $c->get('logger.document') ?? $c->get('logger.api'),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class),
            $c->get(FileStorage::class),
            $c->get(EncryptionService::class),
            $c->get(TemplateService::class),
            $c->get(Document::class),
            $c->get(DocumentTemplate::class),
            $c->get(Contract::class),
            $c->get(User::class),
            $c->get(Booking::class),
            $config['documents'] ?? []
        );
    });

    $container->set(TemplateService::class, function($c) {
        return new TemplateService(
            $c->get('logger.api'),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(MetricsService::class, function($c) {
        return new MetricsService(
            $c->get('logger.metrics'),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
        );
    });

    $container->set(ReportService::class, function($c) {
        return new ReportService(
            $c->get('logger.report'),
            $c->get(ExceptionHandler::class),
            $c->get(Booking::class),
            $c->get(Payment::class),
            $c->get(User::class)
        );
    });

    $container->set(RevenueService::class, function($c) {
        return new RevenueService(
            $c->get('logger.revenue'),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });

    
    $container->set(SignatureService::class, function($c) use ($config) {
        return new SignatureService(
            $c->get('logger.security'),
            $c->get(Signature::class),
            $config['signature'] ?? [],
            $c->get(FileStorage::class),
            $c->get(EncryptionService::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(AuthService::class, function($c) use ($config) {
        return new AuthService(
            $c->get(DatabaseHelper::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class), // Using pre-initialized ExceptionHandler
            $c->get('logger.auth'),
            $c->get(AuditService::class),     // Using pre-initialized AuditService
            $config['encryption'] ?? [],
            $c->get(Validator::class),
            $c->get(User::class)
        );
    });

    $container->set(AdminService::class, function($c) {
        return new AdminService(
            $c->get(Admin::class),
            $c->get(AuditService::class),
            $c->get('logger.admin') ?? $c->get(LoggerInterface::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(BookingService::class, function($c) {
        return new BookingService(
            $c->get('logger.booking'),
            $c->get(ExceptionHandler::class),
            $c->get(Booking::class),
        );
    });

    // Payment related services
    $container->set(PaymentProcessingService::class, function($c) {
        return new PaymentProcessingService(
            $c->get(DatabaseHelper::class),
            $c->get(Payment::class),
            $c->get(Booking::class),
            $c->get(TransactionLog::class),
            $c->get(AuditService::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(RefundService::class, function($c) {
        return new RefundService(
            $c->get(DatabaseHelper::class),
            $c->get(Payment::class),
            $c->get(TransactionLog::class),
            $c->get(AuditService::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(PaymentService::class, function($c) {
        return new PaymentService(
            $c->get(PaymentProcessingService::class),
            $c->get(RefundService::class),
            $c->get(PaymentGatewayService::class),
            $c->get(TransactionService::class),
            $c->get(Payment::class),
            $c->get(PaymentMethod::class),
            $c->get(Booking::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(PayUService::class, function($c) use ($config) {
        return new PayUService(
            $config['payu'] ?? [],
            $c->get('logger.api'),
            $c->get(ExceptionHandler::class)
        );
    });

    // Ensure we're not re-registering services already registered in bootstrap
    // Check if services are already registered before attempting to register them
    
    // AuditLog model - this is not initialized in bootstrap
    if (!$container->has(AuditLog::class)) {
        $container->set(AuditLog::class, function() {
            return new AuditLog();
        });
    }
    
    // Core audit services should be initialized in bootstrap.php
    // Only register if they're not already in the container
    
    // AuditService registration - update to use pre-initialized loggers
    if (!$container->has(AuditService::class)) {
        $container->set(AuditService::class, function($c) use ($loggers, $logger, $config) {
            $requestId = uniqid('audit-', true);
            
            // Get the appropriate logger from the global loggers array
            $auditLogger = $loggers['audit'] ?? $logger;
            
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $logLevelFilter = $c->get(LogLevelFilter::class) ?? new LogLevelFilter($config['audit']['log_levels'] ?? []);
            $auditLog = $c->get(AuditLog::class);
            
            // Get or create sub-services with correct loggers
            $logManager = $c->has(LogManagementService::class) 
                ? $c->get(LogManagementService::class)
                : new LogManagementService($auditLogger, $requestId, $exceptionHandler, $auditLog);
                
            $fraudDetector = $c->has(FraudDetectionService::class) 
                ? $c->get(FraudDetectionService::class)
                : new FraudDetectionService($loggers['security'] ?? $logger, $exceptionHandler);
                
            $userAuditService = $c->has(UserAuditService::class)
                ? $c->get(UserAuditService::class)
                : new UserAuditService($logManager, $exceptionHandler, $auditLogger);
                
            $transactionAuditService = $c->has(TransactionAuditService::class)
                ? $c->get(TransactionAuditService::class)
                : new TransactionAuditService($logManager, $fraudDetector, $exceptionHandler, $loggers['payment'] ?? $logger);
            
            // Create AuditService with the correct logger
            return new AuditService(
                $auditLogger,
                $exceptionHandler,
                $logManager,
                $userAuditService,
                $transactionAuditService,
                $logLevelFilter,
                $auditLog
            );
        });
    }
    
    // Register service dependencies with pre-initialized loggers
    
    // LogManagementService
    if (!$container->has(LogManagementService::class)) {
        $container->set(LogManagementService::class, function($c) use ($loggers, $logger) {
            $auditLogger = $loggers['audit'] ?? $logger;
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $auditLog = $c->get(AuditLog::class);
            $requestId = uniqid('log-', true);
            
            return new LogManagementService(
                $auditLogger,
                $requestId,
                $exceptionHandler,
                $auditLog
            );
        });
    }
    
    // FraudDetectionService
    if (!$container->has(FraudDetectionService::class)) {
        $container->set(FraudDetectionService::class, function($c) use ($loggers, $logger, $config) {
            $securityLogger = $loggers['security'] ?? $logger;
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $fraudConfig = $config['audit']['services']['fraud_detection'] ?? [];
            
            return new FraudDetectionService(
                $securityLogger,
                $exceptionHandler,
                $fraudConfig,
                uniqid('fraud-', true)
            );
        });
    }
    
    // UserAuditService
    if (!$container->has(UserAuditService::class)) {
        $container->set(UserAuditService::class, function($c) use ($loggers, $logger) {
            $auditLogger = $loggers['audit'] ?? $logger;
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $logManager = $c->get(LogManagementService::class);
            
            return new UserAuditService(
                $logManager,
                $exceptionHandler,
                $auditLogger
            );
        });
    }
    
    // TransactionAuditService
    if (!$container->has(TransactionAuditService::class)) {
        $container->set(TransactionAuditService::class, function($c) use ($loggers, $logger) {
            $paymentLogger = $loggers['payment'] ?? $logger;
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $logManager = $c->get(LogManagementService::class);
            $fraudDetector = $c->get(FraudDetectionService::class);
            
            return new TransactionAuditService(
                $logManager,
                $fraudDetector,
                $exceptionHandler,
                $paymentLogger
            );
        });
    }
};
