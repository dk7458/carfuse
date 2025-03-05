<?php

use DI\Container;
use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
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
use App\Services\TransactionService;
use App\Services\PayUService;
use App\Models\User;
use App\Models\RefreshToken;

return function (Container $container, array $config) {

    // Register Models
    $container->set(User::class, function($c) {
        return new User(
            $c->get(DatabaseHelper::class),
            $c->get('db_logger')
        );
    });

    $container->set(RefreshToken::class, function($c) {
        return new RefreshToken(
            $c->get('secure_db'), // Use secure DB for tokens
            $c->get('auth_logger')
        );
    });

    $container->set('bookingModel', function($c) {
        return new App\Models\Booking(
            $c->get(DatabaseHelper::class),
            $c->get('db_logger')
        );
    });

    // Step 8: Register services with proper dependency order
    // First register services that don't depend on other services
    $container->set(Validator::class, function($c) {
        return new Validator(
            $c->get('api_logger'),
            $c->get('db'), // Use main database
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(RateLimiter::class, function($c) {
        return new RateLimiter(
            $c->get('db_logger'),
            $c->get(ExceptionHandler::class)
        );
    });

    // Configure AuditService to use the secure database instance
    $container->set(AuditService::class, function($c) {
        $c->get('dependencies_logger')->info("Creating AuditService instance with secure database");
        return new AuditService(
            $c->get('audit_logger'),
            $c->get(ExceptionHandler::class),
            $c->get('secure_db') // Use secure database instance
        );
    });

    // Update TokenService registration with proper dependencies
    $container->set(TokenService::class, function($c) use ($config) {
        return new TokenService(
            $config['encryption']['jwt_secret'],
            $config['encryption']['jwt_refresh_secret'],
            $c->get('auth_logger'),
            $c->get(ExceptionHandler::class),
            $c->get('db'),
            $c->get(AuditService::class),
            $config['encryption'],
            $c->get(RefreshToken::class),
            $c->get(User::class)
        );
    });

    // Update AuthService registration with proper dependencies
    $container->set(AuthService::class, function($c) use ($config) {
        return new AuthService(
            $c->get('db'),        // Use singleton instance
            $c->get(TokenService::class),          // TokenService
            $c->get(ExceptionHandler::class),      // ExceptionHandler
            $c->get('auth_logger'),                // AuthLogger
            $c->get(AuditService::class),          // Will use pre-initialized instance
            $config['encryption'],                 // Encryption config array
            $c->get(Validator::class),             // Validator
            $c->get(User::class)                   // User model
        );
    });

    $container->set(UserService::class, function($c) {
        return new UserService(
            $c->get('auth_logger'),
            $c->get('db'),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(NotificationService::class, function($c) use ($config) {
        return new NotificationService(
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class),
            $c->get('db'),
            $config['notifications'] ?? []
        );
    });

    $container->set(PaymentService::class, function($c) {
        return new PaymentService(
            $c->get('payment_logger'),
            $c->get('db'),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(BookingService::class, function($c) {
        return new BookingService(
            $c->get('booking_logger'),
            $c->get(ExceptionHandler::class),
            $c->get('db'),
            $c->get('bookingModel')
        );
    });

    $container->set(MetricsService::class, function($c) {
        return new MetricsService(
            $c->get('metrics_logger'),
            $c->get(ExceptionHandler::class),
            $c->get('db')
        );
    });

    $container->set(ReportService::class, function($c) {
        return new ReportService(
            $c->get('report_logger'),
            $c->get('db'),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(RevenueService::class, function($c) {
        return new RevenueService(
            $c->get('revenue_logger'),
            $c->get('db'),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(SignatureService::class, function($c) use ($config) {
        return new SignatureService(
            $c->get('security_logger'),
            $c->get('db'),
            $config['signature'] ?? []
        );
    });

    $container->set(DocumentService::class, function($c) {
        return new DocumentService(
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class),
            $c->get('db')
        );
    });

    $container->set(TemplateService::class, function($c) {
        return new TemplateService(
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(KeyManager::class, function($c) use ($config) {
        return new KeyManager(
            $config['keymanager'],
            $c->get('security_logger'),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(TransactionService::class, function($c) {
        return new TransactionService(
            $c->get('booking_logger'),
            $c->get('db'),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(PayUService::class, function($c) use ($config) {
        return new PayUService(
            $config['payu'] ?? [],
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class)
        );
    });
};
