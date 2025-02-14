<?php

/**
 * Centralized Bootstrap File
 * Path: bootstrap.php
 *
 * Initializes database connections, logging, encryption, and registers necessary services.
 */

use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use DI\Container as DIContainer;
use Illuminate\Database\Capsule\Manager as Capsule;

// ✅ Load Dependencies
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/App/Helpers/DatabaseHelper.php';
define('BASE_PATH', __DIR__);

// ✅ Load Logger
$logger = require_once BASE_PATH . '/logger.php';

// ✅ Load Configuration Files
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

// ✅ Initialize Eloquent ORM for Database Handling
$capsule = new Capsule;
$capsule->addConnection($config['database']['app_database']);
$capsule->addConnection($config['database']['secure_database'], 'secure');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// ✅ Validate Encryption Key
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    $logger->error("❌ Encryption key missing or invalid.");
    die("❌ Error: Encryption key missing or invalid in config/encryption.php\n");
}

// ✅ Initialize Dependency Container
$container = require BASE_PATH . '/config/dependencies.php';

// ✅ Retrieve Critical Services
try {
    $auditService = $container->get(App\Services\AuditService::class);
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
    'db' => $capsule,
    'secure_db' => $capsule->getConnection('secure'),
    'logger' => $logger,
    'auditService' => $auditService,
    'encryptionService' => $encryptionService,
    'container' => $container,
];
