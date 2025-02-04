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

// Load Composer Autoload
require_once __DIR__ . '/vendor/autoload.php';

// Load Configuration Files
$configFiles = ['database', 'encryption', 'dependencies'];
$config = [];

foreach ($configFiles as $file) {
    $path = __DIR__ . "/config/{$file}.php";
    if (!file_exists($path)) {
        die("❌ Error: Missing required configuration file: {$file}.php\n");
    }
    $config[$file] = require $path;
}

// Ensure Database Configuration Exists
if (!isset($config['database']['app_database'], $config['database']['secure_database'])) {
    die("❌ Error: Database configuration is missing or incorrect in config/database.php\n");
}

// Initialize Database (Eloquent ORM)
try {
    $capsule = new Capsule;
    $capsule->addConnection($config['database']['app_database']);
    $capsule->addConnection($config['database']['secure_database'], 'secure');

    $capsule->setEventDispatcher(new Dispatcher(new Container));
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    echo "✅ Eloquent ORM Initialized Successfully.\n";
} catch (Exception $e) {
    die("❌ Eloquent initialization failed: " . $e->getMessage() . "\n");
}

// Fallback PDO Connection for Manual Queries
try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['database']['app_database']['host'],
            $config['database']['app_database']['port'],
            $config['database']['app_database']['database'],
            $config['database']['app_database']['charset']
        ),
        $config['database']['app_database']['username'],
        $config['database']['app_database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Ensure Log Directory Exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

// Initialize Logger (Monolog)
$logFilePath = __DIR__ . '/logs/application.log';
$logger = new Logger('application');

try {
    $streamHandler = new StreamHandler($logFilePath, Logger::DEBUG);
    $streamHandler->setFormatter(new LineFormatter(null, null, true, true));
    $logger->pushHandler($streamHandler);
    echo "✅ Logger Initialized Successfully.\n";
} catch (Exception $e) {
    die("❌ Logger initialization failed: " . $e->getMessage() . "\n");
}

// Initialize Services
try {
    $auditService = new AuditService($pdo);
    $encryptionService = new EncryptionService(); // Uses automatic key loading
} catch (Exception $e) {
    die("❌ Service initialization failed: " . $e->getMessage() . "\n");
}

// Register Middleware (if applicable)
$auditMiddleware = new AuditTrailMiddleware($auditService, $logger);

// Check if all required dependencies exist
$missingDependencies = [];
$requiredServices = ['NotificationService', 'TokenService', 'Validator'];

foreach ($requiredServices as $service) {
    if (!isset($config['dependencies'][$service])) {
        $logger->error("❌ Missing dependency: {$service}");
        $missingDependencies[] = $service;
    }
}

// If dependencies are missing, notify the user
if (!empty($missingDependencies)) {
    echo "⚠️ Missing dependencies detected. Please run `composer dump-autoload` manually.\n";
    die("❌ Resolve missing dependencies and restart the application.");
}


// Ensure Dependencies Are Loaded
foreach ($config['dependencies'] as $dependency) {
    if (is_callable($dependency)) {
        $dependency();
    }
}

// Output Confirmation
echo "✅ Bootstrap process completed successfully.\n";

// Return Configurations for Application Use
return [
    'pdo' => $pdo,
    'logger' => $logger,
    'auditService' => $auditService,
    'encryptionService' => $encryptionService,
];
