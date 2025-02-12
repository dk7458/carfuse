<?php

/**
 * Centralized Bootstrap File
 * Path: bootstrap.php
 *
 * Initializes database connections, logging, and registers necessary services.
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;
use AuditManager\Services\AuditService;
use AuditManager\Middleware\AuditTrailMiddleware;
use App\Services\EncryptionService;

// ✅ Load Dependencies
require_once __DIR__ . '/vendor/autoload.php';
$container = require __DIR__ . '/config/dependencies.php';
define('BASE_PATH', __DIR__); // Set absolute path
require_once __DIR__ . '/config/api.php';

// ✅ Logging Function with Timestamps
function logBootstrapEvent($message)
{
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(__DIR__ . '/logs/bootstrap.log', "{$timestamp} - {$message}\n", FILE_APPEND);
}

// ✅ Ensure Log Directory Exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}
logBootstrapEvent("✅ Log directory verified.");

// ✅ Initialize Logger (Monolog)
$logFilePath = __DIR__ . '/logs/application.log';
$logger = new Logger('application');

try {
    $streamHandler = new StreamHandler($logFilePath, Logger::DEBUG);
    $streamHandler->setFormatter(new LineFormatter(null, null, true, true));
    $logger->pushHandler($streamHandler);
    logBootstrapEvent("✅ Logger initialized successfully.");
} catch (Exception $e) {
    logBootstrapEvent("❌ Logger initialization failed: " . $e->getMessage());
    error_log("❌ Logger initialization failed: " . $e->getMessage());
    die("❌ Logger initialization failed: " . $e->getMessage() . "\n");
}

// Ensure the logger implements LoggerInterface
if (!$logger instanceof LoggerInterface) {
    logBootstrapEvent("❌ Logger must be an instance of LoggerInterface.");
    error_log("❌ Logger must be an instance of LoggerInterface.");
    die("❌ Logger must be an instance of LoggerInterface.\n");
}

// ✅ Retrieve Services from Container
try {
    $pdo = $container->get(PDO::class);
    $notificationService = $container->get(App\Services\NotificationService::class);
    $tokenService = $container->get(App\Services\Auth\TokenService::class);
    $validator = $container->get(App\Services\Validator::class);
    logBootstrapEvent("✅ Services retrieved successfully from the container.");
} catch (Exception $e) {
    $logger->error("❌ Service retrieval failed: " . $e->getMessage());
    die("❌ Service retrieval failed: " . $e->getMessage() . "\n");
}

// ✅ Load Security Helper
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';

// ✅ Load and Validate Configuration Files
$configFiles = ['database', 'encryption', 'keymanager'];
$config = [];

foreach ($configFiles as $file) {
    $path = __DIR__ . "/config/{$file}.php";
    if (!file_exists($path)) {
        logBootstrapEvent("❌ Missing configuration file: {$file}.php");
        die("❌ Error: Missing required configuration file: {$file}.php\n");
    }
    $config[$file] = require $path;
}
logBootstrapEvent("✅ Configuration files loaded successfully.");

// ✅ Ensure Database Configuration Exists
if (!isset($config['database']['app_database'], $config['database']['secure_database'])) {
    logBootstrapEvent("❌ Database configuration missing or incorrect.");
    die("❌ Error: Database configuration missing or incorrect in config/database.php\n");
}

// ✅ Initialize Database (Eloquent ORM)
try {
    $capsule = new Capsule;
    $capsule->addConnection($config['database']['app_database']);
    $capsule->addConnection($config['database']['secure_database'], 'secure');

    $capsule->setEventDispatcher(new Dispatcher(new Container));
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    logBootstrapEvent("✅ Eloquent ORM initialized successfully.");
} catch (Exception $e) {
    logBootstrapEvent("❌ Eloquent initialization failed: " . $e->getMessage());
    $logger->error("❌ Eloquent initialization failed: " . $e->getMessage());
    die("❌ Eloquent initialization failed: " . $e->getMessage() . "\n");
}

// ✅ Ensure Encryption Configuration Exists
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    logBootstrapEvent("❌ Encryption key missing or invalid.");
    die("❌ Error: Encryption key missing or invalid in config/encryption.php\n");
}

// ✅ Retrieve Additional Services from Container
try {
    $auditService = $container->get(AuditService::class);
    $encryptionService = $container->get(EncryptionService::class);
    logBootstrapEvent("✅ Additional services retrieved successfully.");
} catch (Exception $e) {
    logBootstrapEvent("❌ Failed to retrieve additional services: " . $e->getMessage());
    $logger->error("❌ Failed to retrieve additional services: " . $e->getMessage());
    die("❌ Failed to retrieve additional services: " . $e->getMessage() . "\n");
}

// ✅ Register Middleware (if applicable)
$auditMiddleware = new AuditTrailMiddleware($auditService, $logger);

// ✅ Validate Required Dependencies
$missingDependencies = [];
$requiredServices = [
    App\Services\NotificationService::class,
    App\Services\Auth\TokenService::class,
    App\Services\Validator::class
];

foreach ($requiredServices as $service) {
    if (!$container->has($service)) {
        $logger->error("❌ Missing dependency: {$service}");
        $missingDependencies[] = $service;
    }
}

// ✅ Output warnings for missing dependencies
if (!empty($missingDependencies)) {
    logBootstrapEvent("⚠️ Missing dependencies detected: " . implode(', ', $missingDependencies));
    echo "⚠️ Missing dependencies detected: " . implode(', ', $missingDependencies) . "\n";
    echo "⚠️ Ensure dependencies are correctly registered in config/dependencies.php.\n";
}

// ✅ Output Confirmation
logBootstrapEvent("✅ Bootstrap process completed successfully.");

// ✅ Return Configurations for Application Use
return [
    'pdo' => $pdo,
    'logger' => $logger,  // ✅ Now returning proper Monolog Logger instance
    'auditService' => $auditService,
    'encryptionService' => $encryptionService,
];
?>
