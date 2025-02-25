<?php

/**
 * Service Dependencies Configuration
 * 
 * This file contains all service-related dependency registrations for the DI container.
 * It centralizes service registrations to make the main dependencies.php cleaner.
 */

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

/**
 * Register all service dependencies in the container
 * 
 * @param Container $container The DI container
 * @param array $config Configuration values
 * @return Container The container with registered services
 */
function registerServices(Container $container, array $config): Container
{
    // Register basic services first (those with fewer dependencies)
    registerBaseServices($container, $config);
    
    // Register auth services (TokenService and AuthService)
    registerAuthServices($container, $config);
    
    // Register business logic services
    registerBusinessServices($container, $config);
    
    // Register file and document services
    registerDocumentServices($container, $config);
    
    // Register security and audit services
    registerSecurityServices($container, $config);
    
    $container->get(LoggerInterface::class)->info("All services registered successfully");
    
    return $container;
}

/**
 * Register base services (those with minimal dependencies)
 */
function registerBaseServices(Container $container, array $config): void
{
    // Validator service (for input validation)
    $container->set(Validator::class, function($c) {
        return new Validator(
            $c->get('api_logger'),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    // Rate limiter service (for API rate limiting)
    $container->set(RateLimiter::class, function($c) {
        return new RateLimiter(
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class)
        );
    });
    
    // Encryption service (for data encryption)
    $container->set(EncryptionService::class, function($c) use ($config) {
        return new EncryptionService(
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class),
            $config['encryption']['encryption_key'] ?? ''
        );
    });
    
    // Template service (for document templates)
    $container->set(TemplateService::class, function($c) {
        return new TemplateService(
            $c->get('api_logger'),
            __DIR__ . '/../storage/templates',
            $c->get(ExceptionHandler::class)
        );
    });
}

/**
 * Register authentication and authorization services
 */
function registerAuthServices(Container $container, array $config): void
{
    // TokenService for JWT token management
    $container->set(TokenService::class, function($c) use ($config) {
        return new TokenService(
            $config['encryption']['jwt_secret'],
            $config['encryption']['jwt_refresh_secret'],
            3600, // JWT TTL: 1 hour
            604800, // Refresh Token TTL: 7 days
            $c->get('auth_logger'),
            $c->get(ExceptionHandler::class)
        );
    });
    
    // AuthService for user authentication
    $container->set(AuthService::class, function($c) use ($config) {
        return new AuthService(
            $c->get(DatabaseHelper::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class),
            $c->get('auth_logger'),
            $c->get('audit_logger'),
            $config['encryption'],
            $c->get(Validator::class)
        );
    });
}

/**
 * Register core business logic services
 */
function registerBusinessServices(Container $container, array $config): void
{
    // UserService for user management
    $container->set(UserService::class, function($c) {
        return new UserService(
            $c->get(DatabaseHelper::class),
            $c->get('user_logger'),
            $c->get('audit_logger'),
            $c->get(ExceptionHandler::class),
            $c->get(Validator::class)
        );
    });
    
    // NotificationService for sending notifications
    $container->set(NotificationService::class, function($c) use ($config) {
        return new NotificationService(
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class),
            $config['notifications'] ?? []
        );
    });
    
    // PaymentService for payment processing
    $container->set(PaymentService::class, function($c) {
        return new PaymentService(
            $c->get('db_logger'),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    // BookingService for booking management
    $container->set(BookingService::class, function($c) {
        return new BookingService(
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
        );
    });
    
    // MetricsService for analytics and metrics
    $container->set(MetricsService::class, function($c) {
        return new MetricsService(
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
        );
    });
    
    // ReportService for generating reports
    $container->set(ReportService::class, function($c) {
        return new ReportService(
            $c->get('api_logger'),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    // RevenueService for revenue management
    $container->set(RevenueService::class, function($c) {
        return new RevenueService(
            $c->get('db_logger'),
            $c->get(DatabaseHelper::class),
            $c->get(ExceptionHandler::class)
        );
    });
}

/**
 * Register document and file services
 */
function registerDocumentServices(Container $container, array $config): void
{
    // FileStorage for file management
    $container->set(FileStorage::class, function($c) use ($config) {
        return new FileStorage(
            $config['filestorage'],
            $c->get(EncryptionService::class),
            $c->get('api_logger'),
            $c->get(ExceptionHandler::class)
        );
    });
    
    // SignatureService for document signing
    $container->set(SignatureService::class, function($c) use ($config) {
        return new SignatureService(
            $c->get('security_logger'),
            $c->get(DatabaseHelper::class),
            $config['signature'] ?? []
        );
    });
    
    // DocumentService for document management
    $container->set(DocumentService::class, function($c) {
        return new DocumentService(
            $c->get('api_logger'),
            $c->get(AuditService::class),
            $c->get(FileStorage::class),
            $c->get(EncryptionService::class),
            $c->get(TemplateService::class)
        );
    });
}

/**
 * Register security and audit services
 */
function registerSecurityServices(Container $container, array $config): void
{
    // AuditService for audit logging
    $container->set(AuditService::class, function($c) {
        return new AuditService(
            $c->get('security_logger'),
            $c->get(ExceptionHandler::class),
            $c->get(DatabaseHelper::class)
        );
    });
    
    // KeyManager for cryptographic key management
    $container->set(KeyManager::class, function($c) use ($config) {
        return new KeyManager(
            $config['keymanager'] ?? [],
            $c->get('security_logger'),
            $c->get(ExceptionHandler::class)
        );
    });
}

/**
 * Register system helpers
 */
function registerSystemHelpers(Container $container): void
{
    // Register RouterHelper
    $container->set(App\Helpers\RouterHelper::class, function ($c) {
        return new App\Helpers\RouterHelper($c);
    });
}

/**
 * Load service-specific configuration
 * 
 * @param string $configDir Directory containing configuration files
 * @return array Configuration values
 */
function loadServiceConfigs(string $configDir): array
{
    $serviceConfigs = [];
    
    // List of service configuration files to load
    $serviceConfigFiles = [
        'notifications' => 'notifications.php',
        'signature' => 'signature.php',
        'keymanager' => 'keymanager.php'
    ];
    
    foreach ($serviceConfigFiles as $key => $file) {
        $path = "{$configDir}/{$file}";
        if (file_exists($path)) {
            $serviceConfigs[$key] = require $path;
        }
    }
    
    return $serviceConfigs;
}

// If this file is included directly, return a container with registered services
if (!isset($container) && basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    $container = new Container();
    
    // Need basic service dependencies
    require_once __DIR__ . '/logging_dependencies.php';
    $container = registerLoggers($container);
    
    require_once __DIR__ . '/database_dependencies.php';
    $config = require __DIR__ . '/database.php';
    $container = registerDatabases($container, $config);
    
    // Load other configurations
    $config = [
        'encryption' => require __DIR__ . '/encryption.php',
        'filestorage' => require __DIR__ . '/filestorage.php'
    ];
    
    // Register services
    $container = registerServices($container, $config);
    
    return $container;
}
