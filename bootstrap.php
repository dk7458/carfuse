<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Helpers\DatabaseHelper;

// Step 1: Initialize Logger First
require_once __DIR__ . '/logger.php';
$logger = getLogger('system');
if (!$logger instanceof Monolog\Logger) {
    error_log("❌ [BOOTSTRAP] Logger initialization failed. Using fallback logger.");
    $logger = new Monolog\Logger('fallback');
}
$logger->info("🔄 Logger initialized successfully.");

// Step 2: Load Environment Variables
use Dotenv\Dotenv;
$dotenvPath = '/home/u122931475/domains/carfuse.pl/public_html';
$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->load();

// Debugging: Check if .env file exists and is readable
if (!file_exists($dotenvPath)) {
    $logger->critical("❌ ERROR: .env file does not exist at path: {$dotenvPath}");
    exit("❌ ERROR: .env file does not exist at path: {$dotenvPath}\n");
}
if (!is_readable($dotenvPath)) {
    $logger->critical("❌ ERROR: .env file is not readable. Check file permissions: {$dotenvPath}");
    exit("❌ ERROR: .env file is not readable. Check file permissions: {$dotenvPath}\n");
}

if (!$_ENV['DB_HOST']) {
    $logger->critical("❌ ERROR: .env file not loaded correctly. Check file permissions.");
    exit("❌ ERROR: .env file not loaded correctly. Check file permissions.\n");
}
$logger->info("🔄 Environment variables loaded from {$dotenvPath}");

// Step 3: Load Configuration Files
$configFiles = ['database', 'encryption', 'app', 'filestorage'];
$config = [];
foreach ($configFiles as $file) {
    $path = __DIR__ . "/config/{$file}.php";
    if (!file_exists($path)) {
        $logger->critical("❌ Missing configuration file: {$file}.php");
        exit("❌ Missing configuration file: {$file}.php\n");
    }
    $config[$file] = require $path;
    $logger->info("🔄 Configuration file loaded: {$file}.php");
}

// Step 4: Initialize Dependency Injection Container (Load Once)
try {
    $diDependencies = require_once __DIR__ . '/config/dependencies.php';
    $container = $diDependencies['container'];
    if (!$container instanceof \DI\Container) {
        throw new Exception("DI container initialization failed.");
    }
    $container->get('dependencies_logger')->info("✅ Bootstrap: DI container initialized and validated.");
    $logger->info("🔄 Dependencies initialized successfully.");
} catch (Exception $e) {
    $logger->critical("❌ Failed to initialize DI container: " . $e->getMessage());
    exit("❌ DI container initialization failed: " . $e->getMessage() . "\n");
}

// Step 5: Register Logger in DI Container Before Other Services
$container->set(\Psr\Log\LoggerInterface::class, fn() => getLogger('system'));

// Step 6: Load Security Helper and Other Critical Services
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/App/Helpers/DatabaseHelper.php';
require_once __DIR__ . '/App/Helpers/ExceptionHandler.php';
$logger->info("🔄 Security helper and other critical services loaded.");

// Step 7: Load Database Instances
try {
    DatabaseHelper::setLogger($container->get('db_logger'));
    $database = DatabaseHelper::getInstance($config['database']['app_database']);
    $secure_database = DatabaseHelper::getSecureInstance($config['database']['secure_database']);
    $logger->info("🔄 Database instances loaded successfully.");
} catch (Exception $e) {
    $logger->critical("❌ Failed to load database instances: " . $e->getMessage());
    exit("❌ Database initialization failed: " . $e->getMessage() . "\n");
}

// Step 8: Verify Database Connection
try {
    $pdo = $database->getConnection()->getPdo();
    if (!$pdo) {
        throw new Exception("❌ Database connection failed.");
    }
    $container->get('db_logger')->info("✅ Database connection verified successfully.");
} catch (Exception $e) {
    $container->get('db_logger')->critical("❌ Database connection verification failed: " . $e->getMessage());
    exit("❌ Database connection issue: " . $e->getMessage() . "\n");
}

// Step 9: Validate Encryption Key
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    $logger->critical("❌ Encryption key missing or invalid.");
    exit("❌ Critical failure: Encryption key missing or invalid.\n");
}
$logger->info("🔄 Encryption key validated.");

// Step 10: Validate Required Dependencies
$missingDependencies = [];
$requiredServices = [
    \App\Services\NotificationService::class,
    \App\Services\Auth\TokenService::class,
    \App\Services\Validator::class
];
foreach ($requiredServices as $service) {
    if (!$container->has($service)) {
        $missingDependencies[] = $service;
    }
}
if (!empty($missingDependencies)) {
    $logger->error("❌ Missing dependencies: " . implode(', ', $missingDependencies));
    echo "⚠️ Missing dependencies: " . implode(', ', $missingDependencies) . "\n";
    echo "⚠️ Ensure dependencies are correctly registered in config/dependencies.php.\n";
} else {
    $logger->info("🔄 All required dependencies are present.");
}

// Final Step: Return Critical Configurations & DI Container
$logger->info("✅ Bootstrap completed successfully.");
return [
    'db'                => $database,
    'secure_db'         => $secure_database,
    'logger'            => $logger,
    'container'         => $container,
    'auditService'      => $container->get(\App\Services\AuditService::class),
    'encryptionService' => $container->get(\App\Services\EncryptionService::class),
    'config'            => $config, // Pass the configuration array
];

