<?php

/**
 * Centralized Bootstrap File
 * Path: /bootstrap.php
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
define('BASE_PATH', '/home/u122931475/domains/carfuse.pl/public_html'); // Set absolute path

// ✅ Retrieve Services from Container
$pdo = $container->get(PDO::class);
$logger = $container->get(LoggerInterface::class);
$notificationService = $container->get(App\Services\NotificationService::class);
$tokenService = $container->get(App\Services\Auth\TokenService::class);
$validator = $container->get(App\Services\Validator::class);

// ✅ Load Security Helper
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';

// ✅ Load Configuration Files
$configFiles = ['database', 'encryption', 'keymanager'];
$config = [];

foreach ($configFiles as $file) {
    $path = __DIR__ . "/config/{$file}.php";
    if (!file_exists($path)) {
        die("❌ Error: Missing required configuration file: {$file}.php\n");
    }
    $config[$file] = require $path;
}

// ✅ Ensure Database Configuration Exists
if (!isset($config['database']['app_database'], $config['database']['secure_database'])) {
    die("❌ Error: Database configuration is missing or incorrect in config/database.php\n");
}

// ✅ Initialize Database (Eloquent ORM)
try {
    $capsule = new Capsule;
    $capsule->addConnection($config['database']['app_database']);
    $capsule->addConnection($config['database']['secure_database'], 'secure');

    $capsule->setEventDispatcher(new Dispatcher(new Container));
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    //echo "✅ Eloquent ORM Initialized Successfully.\n";
} catch (Exception $e) {
    die("❌ Eloquent initialization failed: " . $e->getMessage() . "\n");
}

// ✅ Ensure Log Directory Exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

// ✅ Initialize Logger (Monolog)
$logFilePath = __DIR__ . '/logs/application.log';
$logger = new Logger('application');

try {
    $streamHandler = new StreamHandler($logFilePath, Logger::DEBUG);
    $streamHandler->setFormatter(new LineFormatter(null, null, true, true));
    $logger->pushHandler($streamHandler);
    //echo "✅ Logger Initialized Successfully.\n";
} catch (Exception $e) {
    die("❌ Logger initialization failed: " . $e->getMessage() . "\n");
}

// ✅ Ensure Encryption Configuration Exists
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    die("❌ Error: Encryption key missing or invalid in config/encryption.php\n");
}

// ✅ Retrieve Additional Services from Container
$auditService = $container->get(AuditService::class);
$encryptionService = $container->get(EncryptionService::class);

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
    echo "⚠️ Missing dependencies detected: " . implode(', ', $missingDependencies) . "\n";
    echo "⚠️ Ensure dependencies are correctly registered in config/dependencies.php.\n";
}

// ✅ Output Confirmation
//echo "✅ Bootstrap process completed successfully.\n";

// ✅ Return Configurations for Application Use
return [
    'pdo' => $pdo,
    'logger' => $logger,
    'auditService' => $auditService,
    'encryptionService' => $encryptionService,
];
