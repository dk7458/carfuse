<?php
// Ensure secure native PHP sessions
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables before anything else
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Include autoloader and SecurityHelper only once
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';

// Load Dependency Container from dependencies.php
$container = require_once __DIR__ . '/config/dependencies.php';

// Load Logger from logger.php and bind LoggerInterface
$logger = require_once __DIR__ . '/logger.php';

// ✅ Ensure Logger is Valid Before Registering It
if (!$logger || !$logger instanceof LoggerInterface) {
    error_log("❌ [BOOTSTRAP] Logger initialization failed. Using fallback logger.");
    $logger = new Monolog\Logger('fallback');
}

// ✅ Bind Logger to DI Container
$container->set(LoggerInterface::class, fn() => $logger);

// Remove redundant DatabaseHelper initialization
// Ensure DatabaseHelper is available globally.
$database = $container->get('db');
$secure_database = $container->get('secure_db');

// Remove Laravel's SessionManager initialization and related Config usage

define('BASE_PATH', __DIR__);
$configFiles = ['encryption', 'keymanager', 'filestorage'];
$config = [];
foreach ($configFiles as $file) {
    $path = BASE_PATH . "/config/{$file}.php";
    if (!file_exists($path)) {
        $logger->error("❌ Missing configuration file: {$file}.php");
        die("❌ Error: Missing required configuration file: {$file}.php\n");
    }
    $config[$file] = require $path;
}
$logger->info("✅ Configuration files loaded successfully.");

// Validate encryption key length
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    $logger->error("❌ Encryption key missing or invalid.");
    die("❌ Error: Encryption key missing or invalid in config/encryption.php\n");
}

// Retrieve required critical services from DI container
try {
    $auditService = $container->get(\App\Services\AuditService::class);
    $encryptionService = $container->get(\App\Services\EncryptionService::class);
    $logger->info("✅ Critical services retrieved successfully.");
} catch (Exception $e) {
    $logger->error("❌ Service retrieval failed: " . $e->getMessage());
    die("❌ Service retrieval failed: " . $e->getMessage() . "\n");
}

// Validate required dependencies
$missingDependencies = [];
$requiredServices = [
    \App\Services\NotificationService::class,
    \App\Services\Auth\TokenService::class,
    \App\Services\Validator::class
];
foreach ($requiredServices as $service) {
    if (!$container->has($service)) {
        $logger->error("❌ Missing dependency: {$service}");
        $missingDependencies[] = $service;
    }
}
if (!empty($missingDependencies)) {
    $logger->warning("⚠️ Missing dependencies: " . implode(', ', $missingDependencies));
    echo "⚠️ Missing dependencies: " . implode(', ', $missingDependencies) . "\n";
    echo "⚠️ Ensure dependencies are correctly registered in config/dependencies.php.\n";
}

// Final configuration return for application use
return [
    'db'              => $database,
    'secure_db'       => $secure_database,
    'logger'          => $logger,
    'auditService'    => $auditService,
    'encryptionService'=> $encryptionService,
    'container'       => $container,
];
