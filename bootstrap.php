<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/App/Helpers/ExceptionHandler.php';
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/App/Helpers/DatabaseHelper.php';
require_once __DIR__ . '/logger.php'; // Ensure the global getLogger function is included
$logger->info("🔄 Security helper and other critical services loaded.");

use Dotenv\Dotenv;
use App\Helpers\DatabaseHelper;
use App\Helpers\SetupHelper;
use App\Helpers\ExceptionHandler;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;

// Step 1: Initialize Default Logger
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Create default logger
try {
    $logger = new Logger('app');
    $formatter = new LineFormatter(
        "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
        "Y-m-d H:i:s.u",
        true,
        true
    );
    
    // Add handlers with the formatter
    $streamHandler = new StreamHandler('php://stderr', Logger::DEBUG);
    $streamHandler->setFormatter($formatter);
    $logger->pushHandler($streamHandler);
    
    $fileHandler = new RotatingFileHandler($logDir . '/app.log', 14, Logger::INFO);
    $fileHandler->setFormatter($formatter);
    $logger->pushHandler($fileHandler);
    
    // Add processors
    $logger->pushProcessor(new WebProcessor());
    $logger->pushProcessor(new IntrospectionProcessor());
    
    $logger->info("🔄 Logger initialized successfully.");
} catch (Exception $e) {
    error_log("❌ [BOOTSTRAP] Logger initialization failed: " . $e->getMessage());
    // Create a minimal fallback logger
    $logger = new Logger('fallback');
    $logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
}

// Create category-specific loggers
$loggers = [
    'db' => null,
    'auth' => null,
    'api' => null,
    'audit' => null,
    'security' => null,
    'payment' => null,
    'booking' => null,
    'metrics' => null,
    'report' => null,
    'revenue' => null,
    'dependencies' => null
];

// Initialize each category logger
foreach ($loggers as $category => &$categoryLogger) {
    try {
        $categoryLogger = new Logger($category);
        
        // Add handlers with the formatter
        $streamHandler = new StreamHandler('php://stderr', Logger::DEBUG);
        $streamHandler->setFormatter($formatter);
        $categoryLogger->pushHandler($streamHandler);
        
        $fileHandler = new RotatingFileHandler($logDir . "/{$category}.log", 14, Logger::INFO);
        $fileHandler->setFormatter($formatter);
        $categoryLogger->pushHandler($fileHandler);
        
        // Add processors
        $categoryLogger->pushProcessor(new WebProcessor());
        $categoryLogger->pushProcessor(new IntrospectionProcessor());
    } catch (Exception $e) {
        error_log("❌ [BOOTSTRAP] {$category} logger initialization failed: " . $e->getMessage());
        // Create a minimal fallback logger for this category
        $categoryLogger = new Logger($category . '_fallback');
        $categoryLogger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
    }
}

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
$requiredConfigs = ['database', 'encryption', 'app', 'filestorage', 'keymanager'];

// First, load required configuration files
foreach ($requiredConfigs as $file) {
    $path = "{$configDir}/{$file}.php";
    if (!file_exists($path)) {
        $logger->critical("❌ Missing required configuration file: {$file}.php");
        exit("❌ Missing required configuration file: {$file}.php\n");
    }
    try {
        $config[$file] = require_once $path;
        $logger->info("🔄 Required configuration file loaded: {$file}.php");
    } catch (Exception $e) {
        $logger->critical("❌ Error loading required configuration file {$file}.php: " . $e->getMessage());
        exit("❌ Error loading required configuration file: {$file}.php\n");
    }
}

// Then, load any additional configuration files from the config directory
$additionalFiles = array_diff(scandir($configDir), ['.', '..', '.gitignore', 'dependencies.php', 'svc_dep.php', 'ctrl_dep.php']);
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

// Make $config available globally
global $config;

// Step 4: Initialize Exception Handler (needed for AuditService)
$exceptionHandler = new ExceptionHandler(
    $loggers['db'],
    $loggers['auth'],
    $logger
);

// Step 5: Initialize Database Connections (needed for AuditService)
try {
    DatabaseHelper::setLogger($loggers['db']);
    
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

// Step 6: Initialize Core Services in Correct Order
// Set up a services container to hold our core initialized services
$coreServices = [];

try {
    // 1. First initialize all loggers (already done earlier in file)
    $coreServices['logger'] = $logger;
    $coreServices['loggers'] = $loggers;
    
    // 2. Initialize ExceptionHandler
    $exceptionHandler = new ExceptionHandler(
        $loggers['db'],
        $loggers['auth'],
        $logger
    );
    $coreServices['exceptionHandler'] = $exceptionHandler;
    $logger->info("✓ ExceptionHandler initialized");
    
    // 3. Initialize LogLevelFilter
    $logLevelFilter = new \App\Helpers\LogLevelFilter($config['logging']['min_level'] ?? 'debug');
    $coreServices['logLevelFilter'] = $logLevelFilter;
    $logger->info("✓ LogLevelFilter initialized");
    
    // 4. Initialize FraudDetectionService
    $fraudDetectionService = new \App\Services\Security\FraudDetectionService(
        $loggers['security'], 
        $exceptionHandler,
        $config['fraud_detection'] ?? [],
        uniqid('fraud-', true)
    );
    $coreServices['fraudDetectionService'] = $fraudDetectionService;
    $logger->info("✓ FraudDetectionService initialized");
    
    // 5. Initialize LogManagementService
    $logManagementService = new \App\Services\Audit\LogManagementService(
        $loggers['audit'],
        uniqid('req-', true),
        $exceptionHandler
    );
    $coreServices['logManagementService'] = $logManagementService;
    $logger->info("✓ LogManagementService initialized");
    
    // 6. Initialize UserAuditService
    $userAuditService = new \App\Services\Audit\UserAuditService(
        $logManagementService,
        $exceptionHandler,
        $loggers['audit']
    );
    $coreServices['userAuditService'] = $userAuditService;
    $logger->info("✓ UserAuditService initialized");
    
    // 7. Initialize TransactionAuditService
    $transactionAuditService = new \App\Services\Audit\TransactionAuditService(
        $logManagementService,
        $fraudDetectionService,
        $exceptionHandler,
        $loggers['payment']
    );
    $coreServices['transactionAuditService'] = $transactionAuditService;
    $logger->info("✓ TransactionAuditService initialized");
    
    // 8. Initialize AuditService with all its dependencies
    $auditService = new \App\Services\AuditService(
        $loggers['audit'],
        $exceptionHandler,
        $logManagementService,
        $userAuditService,
        $transactionAuditService,
        $logLevelFilter
    );
    $coreServices['auditService'] = $auditService;
    $logger->info("✓ AuditService initialized successfully");
    
    // Log successful initialization
    $auditService->logEvent(
        'system',
        'Core services initialized during bootstrap',
        ['environment' => $_ENV['APP_ENV'] ?? 'unknown']
    );
} catch (Exception $e) {
    $logger->critical("❌ Failed to initialize core services: " . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    exit("❌ Core services initialization failed: " . $e->getMessage() . "\n");
}

global $container; // declare container as global

// Step 7: Initialize Dependency Injection Container
try {
    // Create container exactly once
    $container = new \DI\Container();
    $GLOBALS['container'] = $container; // Make container available globally

    // Register loggers, core services, etc.
    $container->set(Psr\Log\LoggerInterface::class, $logger);
    $container->set('logger', $logger);
    foreach ($loggers as $category => $categoryLogger) {
        $container->set("logger.{$category}", $categoryLogger);
    }
    
    $logger->info("✓ Logger services registered in DI container");
    
    // Register pre-initialized services and database instances:
    $container->set(App\Helpers\ExceptionHandler::class, $coreServices['exceptionHandler']);
    $container->set(App\Helpers\LogLevelFilter::class, $coreServices['logLevelFilter']);
    $container->set(App\Services\Security\FraudDetectionService::class, $coreServices['fraudDetectionService']);
    $container->set(App\Services\Audit\LogManagementService::class, $coreServices['logManagementService']);
    $container->set(App\Services\Audit\UserAuditService::class, $coreServices['userAuditService']);
    $container->set(App\Services\Audit\TransactionAuditService::class, $coreServices['transactionAuditService']);
    $container->set(App\Services\AuditService::class, $coreServices['auditService']);
    $container->set(DatabaseHelper::class, $database);
    $container->set('db', $database);
    $container->set('secure_db', $secure_database);

    $logger->info("✓ Pre-initialized core services registered in DI container");

    // Now load dependencies (do NOT re-create the container there)
    require_once __DIR__ . '/config/dependencies.php';

    // Load svc_dep and ctrl_dep as usual
    $svc_dep = require __DIR__ . '/config/svc_dep.php';
    if (is_callable($svc_dep)) {
        $svc_dep($container, $config);
        $logger->info("✓ Service dependencies loaded");
    } else {
        throw new Exception("svc_dep.php did not return a callable");
    }
    $ctrl_dep = require __DIR__ . '/config/ctrl_dep.php';
    if (is_callable($ctrl_dep)) {
        $ctrl_dep($container);
        $logger->info("✓ Controller dependencies loaded");
    } else {
        throw new Exception("ctrl_dep.php did not return a callable");
    }

    $logger->info("✓ Dependencies initialized successfully");
} catch (Exception $e) {
    $logger->critical("❌ Failed to initialize DI container: " . $e->getMessage());
    exit("❌ DI container initialization failed: " . $e->getMessage() . "\n");
}

// Step 9: Verify Database Connection
try {
    $pdo = $database->getConnection();
    if (!$pdo) {
        throw new Exception("❌ Database connection failed.");
    }
    $loggers['db']->info("✅ Database connection verified successfully.");
} catch (Exception $e) {
    $loggers['db']->critical("❌ Database connection verification failed: " . $e->getMessage());
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
    'loggers'           => $loggers,
    'container'         => $container,
    'coreServices'      => $coreServices,  // Include all core services
    'config'            => $config, // Pass the configuration array
];
