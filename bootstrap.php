<?php

/**
 * Centralized Bootstrap File
 * Path: bootstrap.php
 *
 * Initializes database connections, logging, encryption, and registers necessary services.
 */

use DI\Container as DIContainer;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use App\Helpers\DatabaseHelper;

// ✅ Load Dependencies
require_once __DIR__ . '/vendor/autoload.php';
define('BASE_PATH', __DIR__);

// ✅ Load Logger
$logger = require_once BASE_PATH . '/logger.php';

// Remove the Illuminate Container initialization and Facade setup
// ...existing code... (Removed Illuminate\Container\Container and Facade::setFacadeApplication)

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

// ✅ Initialize Database Using DatabaseHelper
$database = DatabaseHelper::getInstance();
$secure_database = DatabaseHelper::getSecureInstance();

// ✅ Validate Encryption Key
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    $logger->error("❌ Encryption key missing or invalid.");
    die("❌ Error: Encryption key missing or invalid in config/encryption.php\n");
}

// ✅ Load Dependency Container from config/dependencies.php
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

if (!empty($missingDependencies)) {
    $logger->warning("⚠️ Missing dependencies detected: " . implode(', ', $missingDependencies));
    echo "⚠️ Missing dependencies detected: " . implode(', ', $missingDependencies) . "\n";
    echo "⚠️ Ensure dependencies are correctly registered in config/dependencies.php.\n";
}

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
