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
    
    // Register DatabaseHelper using its singleton pattern
    $container->set(DatabaseHelper::class, function() {
        return DatabaseHelper::getInstance();
    });
    
    // Register named instances for backward compatibility
    $container->set('db', function() {
        return DatabaseHelper::getInstance();
    });
    
    $container->set('secure_db', function() {
        return DatabaseHelper::getSecureInstance();
    });
    
    $container->get('db_logger')->info("✅ DatabaseHelper registered successfully.");
} catch (Exception $e) {
    $container->get('db_logger')->error("❌ Database initialization failed: " . $e->getMessage());
    die("❌ Database initialization failed. Check logs for details.\n");
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

// Register Models
$container->set(User::class, function($c) {
    return new User(
        $c->get(DatabaseHelper::class)
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
        $c->get(DatabaseHelper::class),
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
        DatabaseHelper::getSecureInstance() // Use secure database instance
    );
});

// Update TokenService registration with proper dependencies
$container->set(TokenService::class, function($c) use ($config) {
    return new TokenService(
        $config['encryption']['jwt_secret'],
        $config['encryption']['jwt_refresh_secret'],
        $c->get('auth_logger'),
        $c->get(ExceptionHandler::class),
        $c->get(DatabaseHelper::class),
        $c->get(AuditService::class) // Will use pre-initialized instance
    );
});

// Update AuthService registration with proper dependencies
$container->set(AuthService::class, function($c) use ($config) {
    return new AuthService(
        $c->get(DatabaseHelper::class),        // Use singleton instance
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
        $c->get(DatabaseHelper::class),
        $c->get(ExceptionHandler::class)
    );
});

$container->set(NotificationService::class, function($c) use ($config) {
    return new NotificationService(
        $c->get('api_logger'),
        $c->get(ExceptionHandler::class),
        $c->get(DatabaseHelper::class),
        $config['notifications'] ?? []
    );
});

$container->set(PaymentService::class, function($c) {
    return new PaymentService(
        $c->get(LoggingHelper::class)->getLoggerByCategory('payment'),
        $c->get(DatabaseHelper::class),
        $c->get(ExceptionHandler::class)
    );
});

$container->set(BookingService::class, function($c) {
    return new BookingService(
        $c->get(LoggingHelper::class)->getLoggerByCategory('booking'),
        $c->get(ExceptionHandler::class),
        $c->get(DatabaseHelper::class),
        $c->get('bookingModel')
    );
});

$container->set(MetricsService::class, function($c) {
    return new MetricsService(
        $c->get(LoggingHelper::class)->getLoggerByCategory('metrics'),
        $c->get(ExceptionHandler::class),
        $c->get(DatabaseHelper::class)
    );
});

$container->set(ReportService::class, function($c) {
    return new ReportService(
        $c->get(LoggingHelper::class)->getLoggerByCategory('report'),
        $c->get(DatabaseHelper::class),
        $c->get(ExceptionHandler::class)
    );
});

$container->set(RevenueService::class, function($c) {
    return new RevenueService(
        $c->get(LoggingHelper::class)->getLoggerByCategory('revenue'),
        $c->get(DatabaseHelper::class),
        $c->get(ExceptionHandler::class)
    );
});

$container->set(SignatureService::class, function($c) use ($config) {
    return new SignatureService(
        $c->get('security_logger'),
        $c->get(DatabaseHelper::class),
        $config['signature'] ?? []
    );
});

$container->set(DocumentService::class, function($c) {
    return new DocumentService(
        $c->get('api_logger'),
        $c->get(ExceptionHandler::class),
        $c->get(DatabaseHelper::class)
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

// Controllers
$container->set(UserController::class, function($c) {
    return new UserController(
        $c->get(Validator::class),
        $c->get(TokenService::class),
        $c->get(ExceptionHandler::class),
        $c->get('api_logger')
    );
});

$container->set(AuthController::class, function($c) {
    return new AuthController(
        $c->get(LoggerInterface::class),
        $c->get(AuthService::class),
        $c->get(TokenService::class),
        $c->get(DatabaseHelper::class)
    );
});

// Additional services with consistent dependency injection
$container->set(TransactionService::class, function($c) {
    return new TransactionService(
        $c->get(LoggingHelper::class)->getLoggerByCategory('booking'),
        $c->get(DatabaseHelper::class),
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

$container->get(LoggerInterface::class)->info("Step 8: Service registration completed.");

// Step 9: Final check for required service registrations and circular dependency detection
$requiredServices = [
    LoggingHelper::class,
    DatabaseHelper::class,
    TokenService::class,
    AuthService::class,
    Validator::class,
    AuditService::class,
    EncryptionService::class,
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
    return [
        'db'                => $container->get(DatabaseHelper::class),
        'secure_db'         => $container->get('secure_db'),
        'logger'            => $container->get(LoggerInterface::class),
        'auditService'      => $container->get(AuditService::class),
        'encryptionService' => $container->get(EncryptionService::class),
        'container'         => $container,
    ];
} catch (Exception $e) {
    $container->get('dependencies_logger')->critical("❌ Security services failed to load: " . $e->getMessage());
    die("❌ Security services failed to load: " . $e->getMessage() . "\n");
}
