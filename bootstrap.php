<?php
// 1. Initialize Logger First
require_once __DIR__ . '/logger.php';
use function getLogger;
$logger = getLogger('system');

// 2. Load Environment Variables AFTER Logger initialization
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Include autoloader and SecurityHelper only once
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';

// 3. Initialize DI Container
$container = new \DI\Container();
// Bind logger immediately.
$container->set('logger', fn() => getLogger('system'));

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
        $logger->critical("❌ Missing configuration file: {$file}.php");
        continue;
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

// 5. Secure Session Initialization Happens Last
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
