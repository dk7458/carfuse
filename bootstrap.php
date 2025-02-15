<?php
require_once __DIR__ . '/vendor/autoload.php'; // Moved to top to load Dotenv

/**
 * Centralized Bootstrap File
 * 
 * Initializes database connections, logging, encryption, and registers necessary services.
 *
 * Path: bootstrap.php
 */

// Load .env before anything else
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Bootstrap Laravel's Container for Facades using unique alias
use Illuminate\Container\Container as LaravelContainer;
use DI\Container as DIContainer;
use Illuminate\Support\Facades\Facade;

$laravelContainer = new LaravelContainer();
LaravelContainer::setInstance($laravelContainer);
Facade::setFacadeApplication($laravelContainer);

define('BASE_PATH', __DIR__);

// ✅ Load Logger
$logger = require_once BASE_PATH . '/logger.php';

// Use require_once for SecurityHelper to avoid multiple inclusions
require_once __DIR__ . '/App/Helpers/SecurityHelper.php';

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

// ✅ Initialize Databases Using DatabaseHelper
try {
    $database = \App\Helpers\DatabaseHelper::getInstance();
    $secure_database = \App\Helpers\DatabaseHelper::getSecureInstance();
    $logger->info("✅ Both databases initialized successfully.");
} catch (Exception $e) {
    $logger->error("❌ Database initialization failed: " . $e->getMessage());
    die("❌ Database initialization failed. Check logs for details.\n");
}

// ✅ Validate Encryption Key
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    $logger->error("❌ Encryption key missing or invalid.");
    die("❌ Error: Encryption key missing or invalid in config/encryption.php\n");
}

// ✅ Load Dependency Container from `config/dependencies.php`
$container = require BASE_PATH . '/config/dependencies.php';

// ✅ Retrieve Critical Services
try {
    $auditService = $container->get(\App\Services\AuditService::class);
    $encryptionService = $container->get(\App\Services\EncryptionService::class);
    $logger->info("✅ Critical services retrieved successfully.");
} catch (Exception $e) {
    $logger->error("❌ Service retrieval failed: " . $e->getMessage());
    die("❌ Service retrieval failed: " . $e->getMessage() . "\n");
}

// Example: Ensure AuthService receives LoggerInterface from the bootstrapped container
$container->set(\App\Services\AuthService::class, fn() => new \App\Services\AuthService($laravelContainer->get(Psr\Log\LoggerInterface::class)));

// Session Handling (using Laravel SessionManager) remains unchanged
use Illuminate\Session\SessionManager;
use Illuminate\Config\Repository as Config;
$container->set(SessionManager::class, function () use ($container) {
	$sessionConfig = [
		'driver' => 'file',
		'files' => __DIR__ . '/../storage/framework/sessions',
		'lifetime' => 120,
		'expire_on_close' => false,
		'encrypt' => false,
		'cookie' => 'carfuse_session',
		'path' => '/',
		'secure' => false,
		'http_only' => true,
		'same_site' => 'lax',
	];
	return new SessionManager(new Config(['session' => $sessionConfig]));
});

// ✅ Validate Required Dependencies
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

// ✅ Warn if Dependencies Are Missing
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
