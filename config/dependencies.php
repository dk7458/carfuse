<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/ExceptionHandler.php';

use DI\Container;
use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\SecurityHelper;
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
use App\Queues\NotificationQueue;
use App\Queues\DocumentQueue;
use App\Models\Payment;
use GuzzleHttp\Client;

// Step 1: Initialize DI Container
try {
    $container = new Container();
    // Register categorized loggers.
    $container->set(LoggerInterface::class, fn() => getLogger('system'));
    $container->set('auth_logger', fn() => getLogger('auth'));
    $container->set('db_logger', fn() => getLogger('db'));
    $container->set('api_logger', fn() => getLogger('api'));
    $container->set('security_logger', fn() => getLogger('security'));
    $container->set('audit_logger', fn() => getLogger('audit')); 

    // Register new dependencies logger.
    $container->set('dependencies_logger', fn() => getLogger('dependencies'));
    $container->get('dependencies_logger')->info("🔄 Step 1: Starting Dependency Injection.");
    $container->get(LoggerInterface::class)->info("Step 1: DI Container created and loggers registered.");
} catch (Exception $e) {
    getLogger('system')->error("❌ [DI] Failed to initialize DI container: " . $e->getMessage());
    die("❌ Dependency Injection container failed: " . $e->getMessage() . "\n");
}

// Register ExceptionHandler after the loggers are available.
$container->set(ExceptionHandler::class, fn($c) => new ExceptionHandler(
    $c->get('db_logger'),
    $c->get('auth_logger'),
    $c->get(LoggerInterface::class)
));

// Add helper registrations immediately after logger registration.
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';
$container->set(SecurityHelper::class, fn() => new SecurityHelper());

// Step 2: Load configuration files.
$container->get(LoggerInterface::class)->info("Step 2: Loading configuration files.");
$configDirectory = __DIR__;
$config = [];
foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');
    if ($fileName !== 'dependencies') {
        $config[$fileName] = require $filePath;
        $container->get(LoggerInterface::class)->info("Configuration file loaded: {$fileName}.php");
    }
}

// Step 3: Initialize DatabaseHelper instances BEFORE services that depend on them.
try {
    $databaseConfig = require __DIR__ . '/database.php';
    $database = new DatabaseHelper($databaseConfig['app_database']);
    $secure_database = new DatabaseHelper($databaseConfig['secure_database']);
    $container->get('db_logger')->info("✅ Both databases initialized successfully.");
} catch (Exception $e) {
    $container->get('db_logger')->error("❌ Database initialization failed: " . $e->getMessage());
    die("❌ Database initialization failed. Check logs for details.\n");
}
$container->set(DatabaseHelper::class, fn() => $database);
$container->set('db', fn() => $database->getCapsule());
$container->set('secure_db', fn() => $secure_database->getCapsule());
$container->get('security_logger')->info("✅ SecurityHelper injected into DI container.");
$container->get('db_logger')->info("✅ DatabaseHelper injected into DI container.");

// Debug database connection before proceeding.
try {
    $pdo = $container->get('db')->getConnection()->getPdo();
    if (!$pdo) {
        throw new Exception("❌ Database connection failed.");
    }
    $container->get('db_logger')->info("✅ Database connection verified successfully.");
} catch (Exception $e) {
    $container->get('db_logger')->critical("❌ Database connection verification failed: " . $e->getMessage());
    die("❌ Database connection issue: " . $e->getMessage() . "\n");
}

// Step 4: Initialize EncryptionService.
$encryptionService = new EncryptionService(
    $container->get(LoggerInterface::class), // Pass the correct logger
    $container->get(ExceptionHandler::class), // Pass the ExceptionHandler
    $config['encryption']['encryption_key'] ?? '' // Pass the encryption key from config
);
$container->set(EncryptionService::class, fn() => $encryptionService);
$container->get(LoggerInterface::class)->info("Step 4: EncryptionService registered.");

// Step 5: Initialize FileStorage using centralized logger.
if (!isset($config['filestorage']) || !is_array($config['filestorage'])) {
    $container->get(LoggerInterface::class)->critical("❌ FileStorage configuration is missing or invalid.");
    die("❌ FileStorage configuration is missing or invalid.\n");
}
$container->set(FileStorage::class, function () use ($container, $config) {
    return new FileStorage(
        $config['filestorage'],  // Pass correct config as an array.
        $container->get('api_logger'), // Pass the proper logger.
        $container->get(EncryptionService::class) // Inject EncryptionService.
    );
});
$container->get(LoggerInterface::class)->info("Step 5: FileStorage registered.");

// Step 6: Load key manager configuration.
$config['keymanager'] = require __DIR__ . '/keymanager.php';
$container->get(LoggerInterface::class)->info("Step 6: Key Manager configuration loaded.");

// Step 7: Ensure required directories exist.
$templateDirectory = __DIR__ . '/../storage/templates';
$fileStorageConfig = $config['filestorage'] ?? [];
if (!is_dir($templateDirectory)) {
    mkdir($templateDirectory, 0775, true);
}
if (!empty($fileStorageConfig['base_directory']) && !is_dir($fileStorageConfig['base_directory'])) {
    mkdir($fileStorageConfig['base_directory'], 0775, true);
}
$container->get(LoggerInterface::class)->info("Step 7: Required directories verified.");

// Step 8: Register services with proper dependency order.
$container->set(Validator::class, fn() => new Validator(
    $container->get('api_logger'), // Pass the logger
    $container->get(DatabaseHelper::class), // Pass the DatabaseHelper
    $container->get(ExceptionHandler::class) // Pass the ExceptionHandler
));
$container->set(RateLimiter::class, fn() => new RateLimiter(
    $container->get('db_logger'), 
    $container->get(ExceptionHandler::class)
));
$container->set(AuditService::class, fn() => new AuditService(
    $container->get('security_logger'),
    $container->get(ExceptionHandler::class),
    $container->get(DatabaseHelper::class)
));
$container->set(TokenService::class, fn() => new TokenService(
    $_ENV['JWT_SECRET'] ?? '',
    $_ENV['JWT_REFRESH_SECRET'] ?? '',
    $container->get('auth_logger'),
    $container->get(ExceptionHandler::class)
));
$container->set(AuthService::class, fn() => new AuthService(
    $container->get(DatabaseHelper::class),  // Ensure DatabaseHelper is injected
    $container->get(TokenService::class),
    $container->get(ExceptionHandler::class),
    $container->get('auth_logger'),
    $container->get('audit_logger'),
    $config['encryption'],
    $container->get(Validator::class) // Inject Validator
));
// Register UserController to receive AuthService via DI.
$container->set(\App\Controllers\UserController::class, fn() => new \App\Controllers\UserController(
    $container->get(\App\Services\Auth\AuthService::class)
));
$container->set(UserService::class, fn() => new UserService(
    $container->get(DatabaseHelper::class),
    $container->get('auth_logger'),
    $container->get('audit_logger')
));
$container->set(NotificationService::class, fn() => new NotificationService(
    $container->get('api_logger'),
    $container->get(ExceptionHandler::class),
    $container->get(DatabaseHelper::class),
    $config['notifications'] ?? []
));
$container->set(PaymentService::class, fn() => new PaymentService(
    $container->get('db_logger'),
    $container->get(DatabaseHelper::class),
    $container->get(ExceptionHandler::class)
));
$container->set(BookingService::class, fn() => new BookingService(
    $container->get('api_logger'),
    $container->get(ExceptionHandler::class),
    $container->get(DatabaseHelper::class)
));
$container->set(MetricsService::class, fn() => new MetricsService(
    $container->get('api_logger'),
    $container->get(ExceptionHandler::class),
    $container->get(DatabaseHelper::class)
));
$container->set(ReportService::class, fn() => new ReportService(
    $container->get('api_logger'),
    $container->get(DatabaseHelper::class),
    $container->get(ExceptionHandler::class)
));
$container->set(RevenueService::class, fn() => new RevenueService(
    $container->get('db_logger'),
    $container->get(DatabaseHelper::class),
    $container->get(ExceptionHandler::class)
));
$container->set(SignatureService::class, fn() => new SignatureService(
    $container->get('security_logger'),
    $container->get(DatabaseHelper::class),
    $config['signature']
));
$container->set(DocumentService::class, fn() => new DocumentService(
    $container->get('api_logger'),
    $container->get(AuditService::class),
    $container->get(FileStorage::class),
    $container->get(EncryptionService::class),
    $container->get(TemplateService::class)
));
$container->set(TemplateService::class, fn() => new TemplateService(
    $container->get('api_logger'),
    __DIR__ . '/../storage/templates',
    $container->get(ExceptionHandler::class)
));
$container->set(FileStorage::class, fn() => new FileStorage(
    $config['filestorage'],
    $container->get(EncryptionService::class),
    $container->get('api_logger'),
    $container->get(ExceptionHandler::class)
));
$container->set(EncryptionService::class, fn() => new EncryptionService(
    $container->get('api_logger'),
    $container->get(ExceptionHandler::class),
    $config['encryption']['encryption_key'] ?? ''
));
$container->set(KeyManager::class, fn() => new KeyManager(
    $config['keymanager'],
    $container->get('security_logger'),
    $container->get(ExceptionHandler::class)
));

// New registrations for additional services ensuring proper logging.
$container->set(\App\Controllers\AuthController::class, fn() => new \App\Controllers\AuthController(
    $container->get(\App\Services\Auth\AuthService::class),
    $container->get(Validator::class),
    $container->get(TokenService::class),
    $container->get(ExceptionHandler::class),
    $container->get('auth_logger'),
    $container->get('audit_logger')
));

$container->get(LoggerInterface::class)->info("Step 8: Service registration completed.");

// Step 9: Final check for required service registrations and circular dependency detection.
$requiredServices = [
    TokenService::class,
    AuthService::class,
    Validator::class,
    DatabaseHelper::class,
];
$container->get('dependencies_logger')->info("🔄 Step 9: Checking for circular dependencies...");
foreach ($requiredServices as $service) {
    try {
        $container->get($service);
        $container->get('dependencies_logger')->info("✅ Service loaded successfully: {$service}");
    } catch (Exception $e) {
        $container->get('dependencies_logger')->critical("❌ Service failed to load: {$service}", ['trace' => $e->getTraceAsString()]);
        die("❌ Service failure: {$service}: " . $e->getMessage() . "\n");
    }
}

$container->get('dependencies_logger')->info("✅ DI container validation completed successfully.");

// Before returning the container, verify security-related services load successfully.
try {
    $container->get(AuthService::class);
    $container->get(Validator::class);
    $container->get(EncryptionService::class);
    $container->get('security_logger')->info("✅ All security-dependent services loaded successfully.");
} catch (Exception $e) {
    $container->get('security_logger')->critical("❌ Security-related service failed: " . $e->getMessage());
    die("❌ Security-related service failure: " . $e->getMessage() . "\n");
}

// Ensure container integrity before return.
return [
    'db'                => $container->get('db'),
    'secure_db'         => $container->get('secure_db'),
    'logger'            => $container->get(LoggerInterface::class),
    'auditService'      => $container->get(AuditService::class),
    'encryptionService' => $container->get(EncryptionService::class),
    'container'         => $container,
];
