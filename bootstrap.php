<?php
// Step 1: Initialize Logger First
require_once __DIR__ . '/logger.php';
$logger = getLogger('system');
if (!$logger instanceof Monolog\Logger) {
    error_log("❌ [BOOTSTRAP] Logger initialization failed. Using fallback logger.");
    $logger = new Monolog\Logger('fallback');
}
$logger->info("🔄 Logger initialized successfully.");

// Step 2: Load Environment Variables AFTER Logger Initialization
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$logger->info("🔄 Environment variables loaded.");

// Step 3: Initialize Dependency Injection Container (Load Once)
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

// Step 4: Register Logger in DI Container Before Other Services
$container->set(\Psr\Log\LoggerInterface::class, fn() => getLogger('system'));

// Step 5: Load Security Helper and Other Critical Services
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';
$logger->info("🔄 Security helper loaded.");

// Step 6: Load Database Instances
try {
    $database = $container->get('db');
    $secure_database = $container->get('secure_db');
    $logger->info("🔄 Database instances loaded successfully.");
} catch (Exception $e) {
    $logger->critical("❌ Failed to load database instances: " . $e->getMessage());
    exit("❌ Database initialization failed: " . $e->getMessage() . "\n");
}

// Step 7: Verify Database Connection
try {
    $pdo = $database->getConnection()->getPdo();
    if (!$pdo) {
        throw new Exception("❌ Database connection failed.");
    }
    $container->get('db_logger')->info("✅ Database connection verified successfully.");
} catch (Exception $e) {
    $container->get('db_logger')->critical("❌ Database connection verification failed: " . $e->getMessage());
    die("❌ Database connection issue: " . $e->getMessage() . "\n");
}

// Step 8: Load Required Configurations
define('BASE_PATH', __DIR__);
$configFiles = ['encryption', 'keymanager', 'filestorage'];
$config = [];
foreach ($configFiles as $file) {
    $path = BASE_PATH . "/config/{$file}.php";
    if (!file_exists($path)) {
        $logger->critical("❌ Missing configuration file: {$file}.php");
        continue;
    }
    $config[$file] = require $path;
    $logger->info("🔄 Configuration file loaded: {$file}.php");
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

// Step 11: Secure Session Initialization Happens Last
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $logger->info("🔄 Session started successfully.");
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
];

