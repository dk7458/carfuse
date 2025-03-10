<?php

use DI\Container;
use Psr\Log\LoggerInterface;

// Helper & Core classes
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\LogLevelFilter;

// Validation & Security
use App\Validation\Validator;
use App\Services\RateLimiter;
use App\Services\EncryptionService;
use App\Services\Security\KeyManager;
use App\Services\Security\FraudDetectionService;

// Authentication services
use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;

// Core services
use App\Services\NotificationService;
use App\Services\UserService;
use App\Services\AdminService;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\StatisticsService;
use App\Services\MetricsService;
use App\Services\ReportService;
use App\Services\RevenueService;
use App\Services\DocumentService;
use App\Services\FileStorage;
use App\Services\TemplateService;
use App\Services\SignatureService;
use App\Services\AuditService;

// Payment sub-services
use App\Services\Payment\PaymentProcessingService;
use App\Services\Payment\RefundService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\TransactionService;
use App\Services\PayUService;

// Audit sub-services
use App\Services\Audit\LogManagementService;
use App\Services\Audit\UserAuditService;
use App\Services\Audit\TransactionAuditService;

// Models
use App\Models\User;
use App\Models\RefreshToken;
use App\Models\Admin;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\TransactionLog;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\Contract;
use App\Models\DocumentQueue;

return function (Container $container, array $config) {
    // Register all services using an optimized approach
    
    // ======== SHARED UTILITY SERVICES ========
    // Core shared services that should be initialized once
    
    // Validator - frequently used, register as shared instance
    $container->set(Validator::class, DI\factory(function($c) {
        return new Validator(
            $c->get('logger.api'),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    })->share(true));
    
    // RateLimiter - shared instance for consistent rate limiting
    $container->set(RateLimiter::class, DI\factory(function($c) {
        return new RateLimiter(
            $c->get('logger.api'),
            $c->get(ExceptionHandler::class)
        );
    })->share(true));
    
    // EncryptionService - shared for consistent encryption across the application
    $container->set(EncryptionService::class, DI\factory(function($c) use ($config) {
        return new EncryptionService(
            $c->get('logger.security'),
            $c->get(ExceptionHandler::class),
            $config['encryption']['key']
        );
    })->share(true));
    
    // ======== MODELS ========
    // Models are generally lightweight but may need specific loggers
    
    $container->set(User::class, function($c) {
        return new User(
            $c->get(DatabaseHelper::class),
            $c->get('logger.db') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(RefreshToken::class, function($c) {
        return new RefreshToken(
            $c->get('secure_db') ?? $c->get(DatabaseHelper::class), 
            $c->get('logger.auth') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(Booking::class, function($c) {
        return new Booking(
            $c->get(DatabaseHelper::class),
            $c->get('logger.booking') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(Admin::class, function($c) {
        return new Admin(
            $c->get(DatabaseHelper::class),
            $c->get('logger.db') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(Payment::class, function($c) {
        return new Payment(
            $c->get(DatabaseHelper::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class)
        );
    });

    $container->set(TransactionLog::class, function($c) {
        return new TransactionLog(
            $c->get(DatabaseHelper::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class)
        );
    });

    // Document related models
    $container->set(Document::class, function($c) {
        return new Document(
            $c->get(DatabaseHelper::class),
            $c->get('logger.document') ?? $c->get(LoggerInterface::class),
            $c->get(AuditService::class)
        );
    });
    
    $container->set(DocumentTemplate::class, function($c) {
        return new DocumentTemplate(
            $c->get(DatabaseHelper::class),
            $c->get('logger.document') ?? $c->get(LoggerInterface::class)
        );
    });
    
    $container->set(Contract::class, function($c) {
        return new Contract(
            $c->get(DatabaseHelper::class),
            $c->get('logger.document') ?? $c->get(LoggerInterface::class)
        );
    });

    // DocumentQueue with proper dependency resolution
    $container->set(DocumentQueue::class, function($c) use ($config) {
        return new DocumentQueue(
            $c->get('logger.document') ?? $c->get(LoggerInterface::class),
            $c->get(FileStorage::class),
            $config['documents'] ?? []
        );
    });
    
    // Set AuditLog if not already registered
    if (!$container->has(AuditLog::class)) {
        $container->set(AuditLog::class, function($c) {
            return new AuditLog(
                $c->get(DatabaseHelper::class),
                $c->get('logger.audit') ?? $c->get(LoggerInterface::class)
            );
        });
    }
    
    // ======== FILE & STORAGE SERVICES ========
    
    $container->set(FileStorage::class, DI\factory(function($c) use ($config) {
        return new FileStorage(
            $config['storage'] ?? [],
            $c->get(EncryptionService::class),
            $c->get('logger.file') ?? $c->get(LoggerInterface::class)
        );
    })->share(true));
    
    // ======== AUTHENTICATION SERVICES ========
    
    $container->set(TokenService::class, function($c) use ($config) {
        return new TokenService(
            $config['encryption'] ?? [],
            $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class),
            $c->get(AuditService::class), 
            $c->get(RefreshToken::class),
            $c->get(User::class)
        );
    });
    
    $container->set(AuthService::class, function($c) use ($config) {
        return new AuthService(
            $c->get(DatabaseHelper::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class),
            $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
            $c->get(AuditService::class),
            $config['encryption'] ?? [],
            $c->get(Validator::class),
            $c->get(User::class)
        );
    });
    
    // ======== PAYMENT SERVICES ========
    
    // Register payment sub-services first
    $container->set(PaymentProcessingService::class, function($c) {
        return new PaymentProcessingService(
            $c->get(DatabaseHelper::class),
            $c->get(Payment::class),
            $c->get(Booking::class)
        );
    });

    $container->set(RefundService::class, function($c) {
        return new RefundService(
            $c->get(DatabaseHelper::class),
            $c->get(Payment::class),
            $c->get(TransactionLog::class)
        );
    });
    
    $container->set(PaymentGatewayService::class, function($c) use ($config) {
        return new PaymentGatewayService(
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class),
            $config['payment_gateways'] ?? []
        );
    });
    
    $container->set(TransactionService::class, function($c) {
        return new TransactionService(
            $c->get(TransactionLog::class),
            $c->get(AuditService::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class)
        );
    });

    // Then register the main PaymentService that uses them
    $container->set(PaymentService::class, function($c) {
        return new PaymentService(
            $c->get(PaymentProcessingService::class),
            $c->get(RefundService::class),
            $c->get(PaymentGatewayService::class),
            $c->get(TransactionService::class),
            $c->get('logger.payment') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    // Additional payment gateway integration
    $container->set(PayUService::class, function($c) use ($config) {
        return new PayUService(
            $config['payu'] ?? [],
            $c->get('logger.api') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class)
        );
    });

    // ======== AUDIT SERVICES ========
    
    // Always check if a service is already registered before attempting to register it
    if (!$container->has(LogLevelFilter::class)) {
        $container->set(LogLevelFilter::class, function($c) use ($config) {
            return new LogLevelFilter($config['audit']['log_levels'] ?? []);
        });
    }
    
    // Register audit sub-services if not already registered
    if (!$container->has(LogManagementService::class)) {
        $container->set(LogManagementService::class, function($c) {
            $logger = $c->get('logger.audit') ?? $c->get(LoggerInterface::class);
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $auditLog = $c->get(AuditLog::class);
            $requestId = uniqid('log-', true);
            
            return new LogManagementService(
                $logger,
                $requestId,
                $exceptionHandler,
                $auditLog
            );
        });
    }
    
    if (!$container->has(FraudDetectionService::class)) {
        $container->set(FraudDetectionService::class, function($c) use ($config) {
            $logger = $c->get('logger.security') ?? $c->get(LoggerInterface::class);
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $fraudConfig = $config['audit']['services']['fraud_detection'] ?? [];
            
            return new FraudDetectionService(
                $logger,
                $exceptionHandler,
                $fraudConfig,
                uniqid('fraud-', true)
            );
        });
    }
    
    if (!$container->has(UserAuditService::class)) {
        $container->set(UserAuditService::class, function($c) {
            $logger = $c->get('logger.audit') ?? $c->get(LoggerInterface::class);
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $logManager = $c->get(LogManagementService::class);
            
            return new UserAuditService(
                $logManager,
                $exceptionHandler,
                $logger
            );
        });
    }
    
    if (!$container->has(TransactionAuditService::class)) {
        $container->set(TransactionAuditService::class, function($c) {
            $logger = $c->get('logger.payment') ?? $c->get(LoggerInterface::class);
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $logManager = $c->get(LogManagementService::class);
            $fraudDetector = $c->get(FraudDetectionService::class);
            
            return new TransactionAuditService(
                $logManager,
                $fraudDetector,
                $exceptionHandler,
                $logger
            );
        });
    }
    
    // Main AuditService - only register if not already available
    if (!$container->has(AuditService::class)) {
        $container->set(AuditService::class, function($c) {
            $logger = $c->get('logger.audit') ?? $c->get(LoggerInterface::class);
            $exceptionHandler = $c->get(ExceptionHandler::class);
            $logManager = $c->get(LogManagementService::class);
            $userAuditService = $c->get(UserAuditService::class);
            $transactionAuditService = $c->get(TransactionAuditService::class);
            $logLevelFilter = $c->get(LogLevelFilter::class);
            $auditLog = $c->get(AuditLog::class);
            
            return new AuditService(
                $logger,
                $exceptionHandler,
                $logManager,
                $userAuditService,
                $transactionAuditService,
                $logLevelFilter,
                $auditLog
            );
        });
    }

    // ======== BUSINESS LOGIC SERVICES ========
    
    // Core business services
    $container->set(UserService::class, function($c) {
        return new UserService(
            $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class),
            $c->get(User::class)
        );
    });

    $container->set(AdminService::class, function($c) {
        return new AdminService(
            $c->get(Admin::class),
            $c->get(AuditService::class),
            $c->get('logger.admin') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    $container->set(NotificationService::class, DI\factory(function($c) use ($config) {
        return new NotificationService(
            $c->get('logger.notification') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class),
            $config['notification'] ?? []
        );
    })->share(true));

    $container->set(BookingService::class, function($c) {
        return new BookingService(
            $c->get('logger.booking') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class),
            $c->get(Booking::class),
            $c->get(User::class)
        );
    });

    $container->set(StatisticsService::class, function($c) {
        return new StatisticsService(
            $c->get('logger.metrics') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
        );
    });
    
    $container->set(MetricsService::class, function($c) {
        return new MetricsService(
            $c->get('logger.metrics') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
        );
    });

    $container->set(ReportService::class, function($c) {
        return new ReportService(
            $c->get('logger.report') ?? $c->get(LoggerInterface::class),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(RevenueService::class, function($c) {
        return new RevenueService(
            $c->get('logger.revenue') ?? $c->get(LoggerInterface::class),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    $container->set(TemplateService::class, DI\factory(function($c) {
        return new TemplateService(
            $c->get('logger.document') ?? $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    })->share(true));
    
    $container->set(SignatureService::class, function($c) use ($config) {
        return new SignatureService(
            $c->get('logger.security') ?? $c->get(LoggerInterface::class),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class),
            $config['signature'] ?? []
        );
    });
    
    $container->set(DocumentService::class, function($c) use ($config) {
        return new DocumentService(
            $c->get('logger.document') ?? $c->get(LoggerInterface::class),
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

    $container->get(LoggerInterface::class)->info("Service dependencies registered successfully");
};
