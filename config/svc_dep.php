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
use App\Models\Payment;
use App\Models\Booking;
use App\Models\TransactionLog;
use App\Services\AdminService;
use App\Services\Payment\PaymentProcessingService;
use App\Services\Payment\RefundService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Security\FraudDetectionService;
use App\Services\Audit\LogManagementService;
use App\Services\Audit\UserAuditService;
use App\Services\Audit\TransactionAuditService;

return function (Container $container, array $config) {
    // Service definitions here, using $container and $config
    // Example:
    // $container->set(\App\Services\MyService::class, function() use ($config) {
    //     return new \App\Services\MyService($config['my_config']);
    // });

    // Register Models
    $container->set(User::class, function($c) {
        return new User(
            $c->get(DatabaseHelper::class),
            $c->get('logger.db')
        );
    });

    $container->set(RefreshToken::class, function($c) {
        return new RefreshToken(
            $c->get('secure_db'), 
            $c->get('logger.auth')
        );
    });

    $container->set('bookingModel', function($c) {
        return new App\Models\Booking(
            $c->get(DatabaseHelper::class),
            $c->get('logger.db')
        );
    });

    $container->set(Admin::class, function($c) {
        return new Admin(
            $c->get(DatabaseHelper::class),
            $c->get('logger.db')
        );
    });

    $container->set(Payment::class, function($c) {
        return new Payment(
            $c->get(DatabaseHelper::class),
            $c->get('logger.payment')
        );
    });

    $container->set(Booking::class, function($c) {
        return new Booking(
            $c->get(DatabaseHelper::class),
            $c->get('logger.booking')
        );
    });

    $container->set(TransactionLog::class, function($c) {
        return new TransactionLog(
            $c->get(DatabaseHelper::class),
            $c->get('logger.payment')
        );
    });

    // Basic services with minimal dependencies
    $container->set(LogManagementService::class, function($c) {
        $requestId = uniqid('req-', true);
        return new LogManagementService(
            $c->get('logger.audit'),
            $requestId,
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(Validator::class, function($c) {
        return new Validator(
            $c->get('logger.api'),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(RateLimiter::class, function($c) {
        return new RateLimiter(
            $c->get('logger.api'),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(PaymentGatewayService::class, function($c) {
        return new PaymentGatewayService(
            $c->get('logger.payment')
        );
    });

    $container->set(EncryptionService::class, function($c) use ($config) {
        return new EncryptionService(
            $c->get('logger.security'),
            $c->get(ExceptionHandler::class),
            $config['encryption']['key']
        );
    });

    $container->set(FraudDetectionService::class, function($c) use ($config) {
        $requestId = uniqid('fraud-', true);
        // Pass the 'fraud_detection' config to the service
        $fraudConfig = $config['fraud_detection'] ?? [];
        return new FraudDetectionService(
            $c->get('logger.security'),
            $c->get(ExceptionHandler::class),
            $fraudConfig,
            $requestId
        );
    });

    // Services with dependencies on basic services
    $container->set(AuditService::class, function($c) {
        return new AuditService(
            $c->get('logger.audit'),
            $c->get(ExceptionHandler::class),
            $c->get(LogManagementService::class),
            $c->get(UserAuditService::class),
            $c->get(TransactionAuditService::class),
            $c->get(LogLevelFilter::class),

        );
    });

    $container->set(UserAuditService::class, function($c) {
        return new UserAuditService(
            $c->get(LogManagementService::class),
            $c->get(ExceptionHandler::class),
            $c->get('logger.audit')
        );
    });

    $container->set(TransactionAuditService::class, function($c) {
        return new TransactionAuditService(
            $c->get(LogManagementService::class),
            $c->get(FraudDetectionService::class),
            $c->get(ExceptionHandler::class),
            $c->get('logger.payment')
        );
    });

    $container->set(FileStorage::class, function($c) use ($config) {
        return new FileStorage(
            $config['storage'] ?? [],
            $c->get(EncryptionService::class),
            $c->get('logger.file')
        );
    });

    $container->set(TokenService::class, function($c) use ($config) {
        return new TokenService(
            $config['encryption'],
            $c->get('logger.auth'),
            $c->get(ExceptionHandler::class),
            $c->get('db'),
            $c->get(AuditService::class),
            $c->get(RefreshToken::class),
            $c->get(User::class)
        );
    });

    $container->set(TransactionService::class, function($c) {
        return new TransactionService(
            $c->get(TransactionLog::class),
            $c->get(AuditService::class),
            $c->get('logger.payment')
        );
    });

    // Update existing service registrations
    $container->set(UserService::class, function($c) {
        return new UserService(
            $c->get('logger.auth'),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(NotificationService::class, function($c) {
        return new NotificationService(
            $c->get('logger.api'),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
        );
    });

    $container->set(DocumentService::class, function($c) {
        return new DocumentService(
            $c->get('logger.api'),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
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
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
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
            $c->get(DatabaseHelper::class),
            $config['signature'] ?? []
        );
    });

    $container->set(AuthService::class, function($c) use ($config) {
        return new AuthService(
            $c->get(DatabaseHelper::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class),
            $c->get('logger.auth'),            // Add LoggerInterface
            $c->get(AuditService::class),      // Add AuditService
            $config['encryption'] ?? [],       // Add encryption config
            $c->get(Validator::class),         // Add Validator
            $c->get(User::class)               // Add User model
        );
    });

    $container->set(AdminService::class, function($c) {
        return new AdminService(
            $c->get(Admin::class),
            $c->get(AuditService::class),
            $c->get('logger.admin')
        );
    });

    $container->set(BookingService::class, function($c) {
        return new BookingService(
            $c->get('logger.booking'),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
        );
    });

    // Payment related services
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

    $container->set(PaymentService::class, function($c) {
        return new PaymentService(
            $c->get(PaymentProcessingService::class),
            $c->get(RefundService::class),
            $c->get(PaymentGatewayService::class)
        );
    });

    $container->set(PayUService::class, function($c) use ($config) {
        return new PayUService(
            $config['payu'] ?? [],
            $c->get('logger.api'),
            $c->get(ExceptionHandler::class)
        );
    });
};
