<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/ExceptionHandler.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';
require_once __DIR__ . '/../App/Helpers/LoggingHelper.php';

use DI\Container;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\SetupHelper;
use App\Helpers\SecurityHelper;
use App\Helpers\LoggingHelper;
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

// Step 1: Initialize DI Container and LoggingHelper
try {
    // Create container
    $container = new Container();
    
    // First register LoggingHelper for centralized logging management
    $container->set(LoggingHelper::class, function() {
        return new LoggingHelper();
    });
    
    // Now register categorized loggers using LoggingHelper
    $loggingHelper = $container->get(LoggingHelper::class);
    $container->set(LoggerInterface::class, fn() => $loggingHelper->getLoggerByCategory('system'));
    $container->set('auth_logger', fn() => $loggingHelper->getLoggerByCategory('auth'));
    $container->set('db_logger', fn() => $loggingHelper->getLoggerByCategory('db'));
    $container->set('api_logger', fn() => $loggingHelper->getLoggerByCategory('api'));
    $container->set('security_logger', fn() => $loggingHelper->getLoggerByCategory('security'));
    $container->set('audit_logger', fn() => $loggingHelper->getLoggerByCategory('audit')); 
    $container->set('dependencies_logger', fn() => $loggingHelper->getLoggerByCategory('dependencies'));
    
    $container->get('dependencies_logger')->info("🔄 Step 1: Starting Dependency Injection.");
    $container->get(LoggerInterface::class)->info("Step 1: DI Container created and loggers registered.");
} catch (Exception $e) {
    $fallbackLogger = new Logger('fallback');
    $fallbackLogger->pushHandler(new StreamHandler('php://stderr', Logger::ERROR));
    $fallbackLogger->error("❌ [DI] Failed to initialize DI container: " . $e->getMessage());
    die("❌ Dependency Injection container failed: " . $e->getMessage() . "\n");
}

// Register ExceptionHandler after the loggers are available
$container->set(ExceptionHandler::class, function($c) {
    return new ExceptionHandler(
        $c->get('db_logger'),
        $c->get('auth_logger'),
        $c->get(LoggerInterface::class)
    );
});

// Add helper registrations
$container->set(SecurityHelper::class, fn() => new SecurityHelper());

// Step 2: Load configuration files
$container->get(LoggerInterface::class)->info("Step 2: Loading configuration files.");
$configDirectory = __DIR__;
$config = [];
$configFiles = ['database', 'encryption', 'app', 'filestorage'];
foreach ($configFiles as $file) {
    $path = "{$configDirectory}/{$file}.php";
    if (!file_exists($path)) {
        $container->get(LoggerInterface::class)->critical("❌ Missing configuration file: {$file}.php");
        die("❌ Missing configuration file: {$file}.php\n");
    }
    $config[$file] = require $path;
    $container->get(LoggerInterface::class)->info("Configuration file loaded: {$file}.php");
}

// Step 3: Initialize DatabaseHelper - CENTRALIZED
try {
    // Set logger for DatabaseHelper
    DatabaseHelper::setLogger($container->get('db_logger'));

    // Ensure correct instance assignments before registering them in the DI container
    DatabaseHelper::getInstance();
    DatabaseHelper::getSecureInstance();

    // Register DatabaseHelper using its singleton pattern
    $container->set(DatabaseHelper::class, function () {
        return DatabaseHelper::getAppInstance();
    });

    // Register named instances for backward compatibility
    $container->set('db', function () {
        return DatabaseHelper::getAppInstance();
    });

    $container->set('secure_db', function () {
        return DatabaseHelper::getSecureDbInstance();
    });

    // Debugging: Log which databases are assigned
    $container->get('db_logger')->info("[BOOTSTRAP] ✅ App Database: " . DatabaseHelper::getAppInstance()->getPdo()->query("SELECT DATABASE()")->fetchColumn());
    $container->get('db_logger')->info("[BOOTSTRAP] ✅ Secure Database: " . DatabaseHelper::getSecureDbInstance()->getPdo()->query("SELECT DATABASE()")->fetchColumn());

} catch (Exception $e) {
    $container->get('db_logger')->critical("[BOOTSTRAP] ❌ Database initialization failed: " . $e->getMessage());
    die("Database initialization failed.");
}


// Debug database connection before proceeding
try {
    $pdo = $container->get(DatabaseHelper::class)->getConnection();
    if (!$pdo) {
        throw new Exception("❌ Database connection failed.");
    }
    $container->get('db_logger')->info("✅ Database connection verified successfully.");
} catch (Exception $e) {
    $container->get('db_logger')->critical("❌ Database connection verification failed: " . $e->getMessage());
    die("❌ Database connection issue: " . $e->getMessage() . "\n");
}

// Step 4: Initialize EncryptionService
$container->set(EncryptionService::class, function($c) use ($config) {
    return new EncryptionService(
        $c->get(LoggerInterface::class),
        $c->get(ExceptionHandler::class),
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
        $c->get('api_logger'),
        $c->get(ExceptionHandler::class)
    );
});
$container->get(LoggerInterface::class)->info("Step 5: FileStorage registered.");

// Step 6: Load key manager configuration
$config['keymanager'] = require __DIR__ . '/keymanager.php';
$container->get(LoggerInterface::class)->info("Step 6: Key Manager configuration loaded.");

// Step 7: Ensure required directories exist
$templateDirectory = __DIR__ . '/../storage/templates';
if (!is_dir($templateDirectory)) {
    mkdir($templateDirectory, 0775, true);
}
$container->get(LoggerInterface::class)->info("Step 7: Required directories verified.");

// Include service and controller definitions
$svc_dep = require __DIR__ . '/svc_dep.php';
$svc_dep($container, $config);

$ctrl_dep = require __DIR__ . '/ctrl_dep.php';
$ctrl_dep($container);

$container->get(LoggerInterface::class)->info("Step 8: Service and Controller registration completed.");

// Step 9: Final check for required service registrations and circular dependency detection
$requiredServices = [
    LoggingHelper::class,
    DatabaseHelper::class,
    TokenService::class,
    AuthService::class,
    Validator::class,
    AuditService::class,
    EncryptionService::class,
    RateLimiter::class,
];

$container->get('dependencies_logger')->info("🔄 Step 9: Checking for circular dependencies...");
$failedServices = [];

foreach ($requiredServices as $service) {
    try {
        $container->get($service);
        $container->get('dependencies_logger')->info("✅ Service loaded successfully: {$service}");
    } catch (Exception $e) {
        $errorMsg = "❌ Service failed to load: {$service}: " . $e->getMessage();
        $container->get('dependencies_logger')->critical($errorMsg, ['trace' => $e->getTraceAsString()]);
        $failedServices[] = $errorMsg;
    }
}

if (!empty($failedServices)) {
    die("❌ Service failures: " . implode("\n", $failedServices) . "\n");
}

$container->get('dependencies_logger')->info("✅ DI container validation completed successfully.");

// Verify AuditService is properly initialized
try {
    $auditService = $container->get(AuditService::class);
    $container->get('dependencies_logger')->info("✅ AuditService verification successful");
    $auditService->logEvent('system', 'Dependencies loaded successfully', ['source' => 'dependencies.php']);
} catch (Exception $e) {
    $container->get('dependencies_logger')->critical("❌ AuditService verification failed: " . $e->getMessage());
}

// Before returning the container, verify security-related services load successfully
try {
    $container->get(AuthService::class);
    $result = [
        'db'                => $container->get(DatabaseHelper::class),
        'secure_db'         => $container->get('secure_db'),
        'logger'            => $container->get(LoggerInterface::class),
        'auditService'      => $container->get(AuditService::class),
        'encryptionService' => $container->get(EncryptionService::class),
        'container'         => $container,
    ];
    return $result;
} catch (Exception $e) {
    $container->get('dependencies_logger')->critical("❌ Security services failed to load: " . $e->getMessage());
    die("❌ Security services failed to load: " . $e->getMessage() . "\n");
}
