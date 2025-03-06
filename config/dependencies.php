<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/ExceptionHandler.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';
require_once __DIR__ . '/../logger.php'; // Direct inclusion of logger

use DI\Container;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\SetupHelper;
use App\Helpers\LogLevelFilter;
use App\Helpers\SecurityHelper;
use App\Middleware\RequireAuthMiddleware;
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
use App\Queues\NotificationQueue;
use App\Queues\DocumentQueue;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\User;

use GuzzleHttp\Client;
use App\Controllers\UserController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\NotificationController;
use App\Controllers\AdminController;
use App\Controllers\SignatureController;
use App\Controllers\DashboardController;
use App\Controllers\AdminDashboardController;
use App\Controllers\PaymentController;
use App\Controllers\DocumentController;
use App\Controllers\ReportController;
use App\Controllers\AuditController;
use Psr\Http\Message\ResponseFactoryInterface;

// Make $config available to the container
global $config;

// Access the global logger variable instead of using the container initially
global $logger;

// Step 1: Retrieve the pre-created container from $GLOBALS
if (isset($GLOBALS['container']) && $GLOBALS['container'] instanceof Container) {
    $container = $GLOBALS['container'];
    $logger->info("🔄 Step 1: Using pre-configured container from bootstrap.");
} else {
    $logger->critical("❌ Container not properly initialized in bootstrap.php");
    die("❌ Dependency Injection container failed: Container not properly initialized in bootstrap.php\n");
}

// Note: ExceptionHandler is now initialized in bootstrap.php
// No need to register it here as it will be set from bootstrap.php

// Add helper registrations
if (!$container->has(SecurityHelper::class)) {
    $container->set(SecurityHelper::class, fn() => new SecurityHelper());
}

// Note: LogLevelFilter is now initialized in bootstrap.php
// No need to register it here

// Note: DatabaseHelper initialization is handled in bootstrap.php 
// and registered to the container from there

// Step 4: Initialize EncryptionService
$container->set(EncryptionService::class, function($c) use ($config) {
    return new EncryptionService(
        $c->get(LoggerInterface::class),
        $c->get(ExceptionHandler::class), // Using pre-initialized ExceptionHandler
        $config['encryption']['encryption_key']
    );
});
$container->get(LoggerInterface::class)->info("Step 4: EncryptionService registered.");

// Step 5: Initialize FileStorage
if (!isset($config['filestorage']) || !is_array($config['filestorage'])) {
    $container->get(LoggerInterface::class)->critical("❌ FileStorage configuration is missing or invalid.");
    die("❌ FileStorage configuration is missing or invalid.\n");
}
$container->set(FileStorage::class, function($c) use ($config) {
    return new FileStorage(
        $config['filestorage'],
        $c->get(EncryptionService::class),
        $c->get('logger.api'),
        $c->get(ExceptionHandler::class)
    );
});
$container->get(LoggerInterface::class)->info("Step 5: FileStorage registered.");

// Step 6: Load key manager configuration - Handled in bootstrap
$container->get(LoggerInterface::class)->info("Step 6: Key Manager configuration loaded.");

// Step 7: Ensure required directories exist
$templateDirectory = __DIR__ . '/../storage/templates';
if (!is_dir($templateDirectory)) {
    mkdir($templateDirectory, 0775, true);
}
$container->get(LoggerInterface::class)->info("Step 7: Required directories verified.");

// Include service and controller definitions
$svc_dep = require __DIR__ . '/svc_dep.php';
if (is_callable($svc_dep)) {
    $svc_dep($container, $config);
} else {
    error_log("svc_dep.php did not return a callable value.");
    die("❌ svc_dep.php is not callable.\n");
}

$ctrl_dep = require __DIR__ . '/ctrl_dep.php';
if (is_callable($ctrl_dep)) {
    $ctrl_dep($container);
} else {
    error_log("ctrl_dep.php did not return a callable value.");
    die("❌ ctrl_dep.php is not callable.\n");
}

$container->get(LoggerInterface::class)->info("Step 8: Service and Controller registration completed.");

// Step 9: Final check for required service registrations and circular dependency detection
$requiredServices = [
    DatabaseHelper::class,
    TokenService::class,
    AuthService::class,
    Validator::class,
    AuditService::class,
    EncryptionService::class,
    RateLimiter::class,
];

$container->get('logger.dependencies')->info("🔄 Step 9: Checking for circular dependencies...");
$failedServices = [];

foreach ($requiredServices as $service) {
    try {
        $container->get($service);
        $container->get('logger.dependencies')->info("✅ Service loaded successfully: {$service}");
    } catch (Exception $e) {
        $errorMsg = "❌ Service failed to load: {$service}: " . $e->getMessage();
        $container->get('logger.dependencies')->critical($errorMsg, ['trace' => $e->getTraceAsString()]);
        $failedServices[] = $errorMsg;
    }
}

if (!empty($failedServices)) {
    die("❌ Service failures: " . implode("\n", $failedServices) . "\n");
}

$container->get('logger.dependencies')->info("✅ DI container validation completed successfully.");

// Verify AuditService is properly initialized
try {
    $auditService = $container->get(AuditService::class);
    $container->get('logger.dependencies')->info("✅ AuditService verification successful");
    $auditService->logEvent('system', 'Dependencies loaded successfully', ['source' => 'dependencies.php']);
} catch (Exception $e) {
    $container->get('logger.dependencies')->critical("❌ AuditService verification failed: " . $e->getMessage());
}

// Before returning the container, verify security-related services load successfully
try {
    $container->get(AuthService::class);
    $result = [
        'db'                => $container->get(DatabaseHelper::class),
        'secure_db'         => $container->get('secure_db'),
        'logger'            => $container->get(LoggerInterface::class),
        'auditService'      => $container->get(AuditService::class), // Pre-initialized in bootstrap
        'container'         => $container,
    ];
    return $result;
} catch (Exception $e) {
    $container->get('logger.dependencies')->critical("❌ Security services failed to load: " . $e->getMessage());
    die("❌ Security services failed to load: " . $e->getMessage() . "\n");
}
