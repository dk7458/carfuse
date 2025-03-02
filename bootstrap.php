<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/App/Helpers/ExceptionHandler.php';
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/App/Helpers/DatabaseHelper.php';
require_once __DIR__ . '/App/Helpers/LoggingHelper.php'; // Ensure LoggingHelper is included
require_once __DIR__ . '/logger.php'; // Ensure the global getLogger function is included
$logger->info("🔄 Security helper and other critical services loaded.");

use Dotenv\Dotenv;
use App\Helpers\DatabaseHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\SetupHelper;
use App\Helpers\ExceptionHandler;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

// Step 1: Initialize Logger First
$loggingHelper = new LoggingHelper();
$logger = $loggingHelper->getDefaultLogger();
if (!$logger instanceof Monolog\Logger) {
    error_log("❌ [BOOTSTRAP] Logger initialization failed. Using fallback logger.");
    $logger = new Monolog\Logger('fallback');
}
$logger->info("🔄 Logger initialized successfully.");

// Step 2: Load Environment Variables
$dotenvPath = __DIR__;
$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->load();

// Debugging: Check if .env file exists and is readable
if (!file_exists($dotenvPath . '/.env')) {
    $logger->critical("❌ ERROR: .env file does not exist at path: {$dotenvPath}");
    exit("❌ ERROR: .env file does not exist at path: {$dotenvPath}\n");
}
if (!is_readable($dotenvPath . '/.env')) {
    $logger->critical("❌ ERROR: .env file is not readable. Check file permissions: {$dotenvPath}");
    exit("❌ ERROR: .env file is not readable. Check file permissions: {$dotenvPath}\n");
}

if (!$_ENV['DB_HOST']) {
    $logger->critical("❌ ERROR: .env file not loaded correctly. Check file permissions.");
    exit("❌ ERROR: .env file not loaded correctly. Check file permissions.\n");
}
$logger->info("🔄 Environment variables loaded from {$dotenvPath}");

// Step 3: Load Configuration Files Dynamically
$config = [];
$configDir = __DIR__ . '/config';
$requiredConfigs = ['database', 'encryption', 'app', 'filestorage'];

// First, load required configuration files
foreach ($requiredConfigs as $file) {
    $path = "{$configDir}/{$file}.php";
    if (!file_exists($path)) {
        $logger->critical("❌ Missing required configuration file: {$file}.php");
        exit("❌ Missing required configuration file: {$file}.php\n");
    }
    try {
        $config[$file] = require $path;
        $logger->info("🔄 Required configuration file loaded: {$file}.php");
    } catch (Exception $e) {
        $logger->critical("❌ Error loading required configuration file {$file}.php: " . $e->getMessage());
        exit("❌ Error loading required configuration file: {$file}.php\n");
    }
}

// Then, load any additional configuration files from the config directory
$additionalFiles = array_diff(scandir($configDir), ['.', '..', '.gitignore']);
foreach ($additionalFiles as $file) {
    // Skip already loaded required configs and non-PHP files
    $filename = pathinfo($file, PATHINFO_FILENAME);
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    
    if ($extension !== 'php' || in_array($filename, $requiredConfigs)) {
        continue;
    }
    
    try {
        $config[$filename] = require "{$configDir}/{$file}";
        $logger->info("🔄 Additional configuration file loaded: {$file}");
    } catch (Exception $e) {
        $logger->warning("⚠️ Error loading optional configuration file {$file}: " . $e->getMessage());
        // Don't exit for optional configs
    }
}

$logger->info("✅ All configuration files loaded successfully.");

// Step 4: Initialize Exception Handler (needed for AuditService)
$exceptionHandler = new ExceptionHandler(
    $loggingHelper->getLoggerByCategory('db'),
    $loggingHelper->getLoggerByCategory('auth'),
    $logger
);

// Step 5: Initialize Database Connections (needed for AuditService)
try {
    DatabaseHelper::setLogger($loggingHelper->getLoggerByCategory('db'));
    
    // Explicitly initialize both database instances
    $database = DatabaseHelper::getInstance();
    $secure_database = DatabaseHelper::getSecureInstance();
    
    // Verify which databases are actually being used
    $logger->info("🔍 [Database Check] App database: " . $database->getPdo()->query("SELECT DATABASE()")->fetchColumn());
    $logger->info("🔍 [Database Check] Secure database: " . $secure_database->getPdo()->query("SELECT DATABASE()")->fetchColumn());
    
    $logger->info("🔄 Database instances loaded successfully.");
} catch (Exception $e) {
    $logger->critical("❌ Failed to load database instances: " . $e->getMessage());
    exit("❌ Database initialization failed: " . $e->getMessage() . "\n");
}

// Step 6: Initialize AuditService early with audit logger
try {
    $auditLogger = $loggingHelper->getLoggerByCategory('audit');
    $auditService = new AuditService($auditLogger, $exceptionHandler, $secure_database);
    $auditService->logEvent(
        'system',
        'AuditService initialized during bootstrap',
        ['environment' => $_ENV['APP_ENV'] ?? 'unknown']
    );
    $logger->info("✅ AuditService initialized successfully early in bootstrap.");
} catch (Exception $e) {
    $logger->critical("❌ Failed to initialize AuditService: " . $e->getMessage());
    exit("❌ AuditService initialization failed: " . $e->getMessage() . "\n");
}

// Step 7: Initialize Dependency Injection Container
try {
    $container = new \DI\Container();
    $diDependencies = require_once __DIR__ . '/config/dependencies.php';
    $container = $diDependencies['container'];
    
    // Register our pre-initialized AuditService in the container
    if ($container instanceof \DI\Container) {
        $container->set(AuditService::class, $auditService);
        $logger->info("✅ Pre-initialized AuditService registered in DI container.");
    } else {
        throw new Exception("DI container initialization failed.");
    }
    
    $logger->info("🔄 Dependencies initialized successfully.");
} catch (Exception $e) {
    $logger->critical("❌ Failed to initialize DI container: " . $e->getMessage());
    exit("❌ DI container initialization failed: " . $e->getMessage() . "\n");
}

// Step 8: Register Logger in DI Container
$container->set(LoggerInterface::class, fn() => $loggingHelper->getDefaultLogger());

// Step 9: Verify Database Connection
try {
    $pdo = $database->getConnection();
    if (!$pdo) {
        throw new Exception("❌ Database connection failed.");
    }
    $container->get(LoggingHelper::class)->getLoggerByCategory('db')->info("✅ Database connection verified successfully.");
} catch (Exception $e) {
    $container->get(LoggingHelper::class)->getLoggerByCategory('db')->critical("❌ Database connection verification failed: " . $e->getMessage());
    exit("❌ Database connection issue: " . $e->getMessage() . "\n");
}

// Step 10: Validate Encryption Key
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    $logger->critical("❌ Encryption key missing or invalid.");
    exit("❌ Critical failure: Encryption key missing or invalid.\n");
}
$logger->info("🔄 Encryption key validated.");

// Step 11: Validate Required Dependencies
$missingDependencies = [];
$requiredServices = [
    NotificationService::class,
    TokenService::class,
    Validator::class,
    AuditService::class // Added AuditService to required services
];
if (!empty($missingDependencies)) {
    $logger->error("❌ Missing dependencies: " . implode(', ', $missingDependencies));
    echo "⚠️ Missing dependencies: " . implode(', ', $missingDependencies) . "\n";
    echo "⚠️ Ensure dependencies are correctly registered in config/dependencies.php.\n";
} else {
    $logger->info("🔄 All required dependencies are present.");
}

// Run initial setup tasks
try {
    // Ensure database indexes exist
    $setupHelper = $container->get(SetupHelper::class);
    $setupHelper->ensureIndexes();
    
    // Check environment security
    $securityIssues = $setupHelper->verifySecureEnvironment();
    if (!empty($securityIssues)) {
        $logger->warning("Security issues detected:", ['issues' => $securityIssues]);
    } else {
        $logger->info("Environment security checks passed");
    }
    
    // Log successful bootstrap via pre-initialized AuditService
    $auditService->logEvent(
        'system',
        'Application bootstrap completed successfully',
        ['environment' => $_ENV['APP_ENV'] ?? 'unknown']
    );
    
    $logger->info("Application bootstrap completed successfully");
} catch (Exception $e) {
    $logger->critical("Bootstrap failed: " . $e->getMessage(), [
        'exception' => get_class($e),
        'trace' => $e->getTraceAsString()
    ]);
    die("Application failed to start: " . $e->getMessage());
}

// Final Step: Return Critical Configurations & DI Container
$logger->info("✅ Bootstrap completed successfully.");
return [
    'db'                => $database,
    'secure_db'         => $secure_database,
    'logger'            => $logger,
    'container'         => $container,
    'auditService'      => $auditService, // Return the pre-initialized audit service
    'encryptionService' => $container->get(\App\Services\EncryptionService::class),
    'config'            => $config, // Pass the configuration array
];
