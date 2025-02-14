<?php

/**
 * Centralized Bootstrap File
 * 
 * Initializes database connections, logging, encryption, and registers necessary services.
 *
 * Path: bootstrap.php
 */

use DI\Container as DIContainer;
use App\Helpers\DatabaseHelper;

// ✅ Load Dependencies
require_once __DIR__ . '/vendor/autoload.php';
define('BASE_PATH', __DIR__);

// ✅ Load Logger
$logger = require_once BASE_PATH . '/logger.php';

// ✅ Initialize Dependency Container (PHP-DI)
$container = new DIContainer();

// ✅ Load Configuration Files
$configFiles = ['encryption', 'keymanager', 'filestorage'];
$config = [];

foreach ($configFiles as $file) {
    $path = BASE_PATH . "/config/{$file}.php";
    if (!file_exists($path)) {
        $logger->error("❌ Missing configuration file: {$file}.php");
        continue; // ✅ Skip missing config instead of terminating
    }
    $config[$file] = require $path;
}
$logger->info("✅ Configuration files loaded successfully.");

// ✅ Initialize Both Databases Using `DatabaseHelper`
try {
    $database = DatabaseHelper::getInstance();        // Main database
    $secure_database = DatabaseHelper::getSecureInstance(); // Secure database
    $logger->info("✅ Both databases initialized successfully.");
} catch (Exception $e) {
    $logger->error("❌ Database initialization failed: " . $e->getMessage());
    die("❌ Database initialization failed. Check logs for details.\n");
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
    'db' => $database,
    'secure_db' => $secure_database,
    'logger' => $logger,
    'auditService' => $auditService,
    'encryptionService' => $encryptionService,
    'container' => $container,
];
