<?php

/**
 * Centralized Bootstrap File
 * Path: bootstrap.php
 *
 * Initializes database connections, logging, encryption, and registers necessary services.
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use DI\Container as DIContainer;

// ✅ Load Dependencies
require_once __DIR__ . '/vendor/autoload.php';
define('BASE_PATH', __DIR__);

// ✅ Load Logger
$logger = require_once BASE_PATH . '/logger.php';

// ✅ Load and Validate Configuration Files
$configFiles = ['database', 'encryption', 'keymanager'];
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

// ✅ Ensure Database Configuration Exists
if (!isset($config['database']['app_database'], $config['database']['secure_database'])) {
    $logger->error("❌ Database configuration missing or incorrect.");
    die("❌ Error: Database configuration missing or incorrect in config/database.php\n");
}

// ✅ Initialize Eloquent ORM
try {
    $capsule = new Capsule;
    $capsule->addConnection($config['database']['app_database'], 'default');
    $capsule->addConnection($config['database']['secure_database'], 'secure');
    $capsule->setEventDispatcher(new Dispatcher(new Container));
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    $logger->info("✅ Eloquent ORM initialized successfully.");
} catch (Exception $e) {
    $logger->error("❌ Eloquent initialization failed: " . $e->getMessage());
    die("❌ Eloquent initialization failed: " . $e->getMessage() . "\n");
}

// ✅ Ensure Encryption Configuration Exists
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    $logger->error("❌ Encryption key missing or invalid.");
    die("❌ Error: Encryption key missing or invalid in config/encryption.php\n");
}

// ✅ Initialize Dependency Container
$container = require BASE_PATH . '/config/dependencies.php';

// ✅ Retrieve Critical Services
try {
    $auditService = $container->get(AuditManager\Services\AuditService::class);
    $encryptionService = $container->get(App\Services\EncryptionService::class);
    $logger->info("✅ Critical services retrieved successfully.");
} catch (Exception $e) {
    $logger->error("❌ Service retrieval failed: " . $e->getMessage());
    die("❌ Service retrieval failed: " . $e->getMessage() . "\n");
}

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
    $logger->warning("⚠️ Missing dependencies detected: " . implode(', ', $missingDependencies));
    echo "⚠️ Missing dependencies detected: " . implode(', ', $missingDependencies) . "\n";
    echo "⚠️ Ensure dependencies are correctly registered in config/dependencies.php.\n";
}

// ✅ Final Confirmation
$logger->info("✅ Bootstrap process completed successfully.");

// ✅ Return Configurations for Application Use
return [
    'db' => $capsule->getConnection(),
    'secure_db' => $capsule->getConnection('secure'),
    'logger' => $logger,
    'auditService' => $auditService,
    'encryptionService' => $encryptionService,
    'container' => $container,
];
