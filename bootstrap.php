<?php

/**
 * Centralized Bootstrap File
 * Path: /bootstrap.php
 *
 * This file initializes database connections, loads environment variables,
 * sets up logging, and registers necessary services across the application.
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Psr\Log\LoggerInterface;
use AuditManager\Services\AuditService;
use AuditManager\Middleware\AuditTrailMiddleware;
use DocumentManager\Services\EncryptionService;

// Load Composer Autoload
require_once __DIR__ . '/vendor/autoload.php';

// Load Environment Variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (InvalidPathException $e) {
    echo "Environment file missing: " . $e->getMessage();
    exit(1);
}

// Load Configuration Files
$config = [
    'database' => require __DIR__ . '/config/database.php',
    'encryption' => require __DIR__ . '/config/encryption.php',
    'dependencies' => require __DIR__ . '/config/dependencies.php',
];

// Initialize Database (Eloquent ORM with PDO)
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $config['database']['host'],
    'database'  => $config['database']['database'],
    'username'  => $config['database']['username'],
    'password'  => $config['database']['password'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setEventDispatcher(new Dispatcher(new Container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Fallback PDO Connection
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database'],
        $config['database']['charset']
    );

    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize Logger (Monolog)
$logFilePath = __DIR__ . '/logs/application.log';
$logger = new Logger('application');
$streamHandler = new StreamHandler($logFilePath, Logger::DEBUG);
$streamHandler->setFormatter(new LineFormatter(null, null, true, true));
$logger->pushHandler($streamHandler);

// Initialize Services
$auditService = new AuditService($pdo);
$encryptionService = new EncryptionService($config['encryption']);

// Register Middleware (if applicable)
$auditMiddleware = new AuditTrailMiddleware($auditService, $logger);

// Check Required Dependencies
$requiredServices = ['NotificationService', 'TokenService', 'Validator'];
foreach ($requiredServices as $service) {
    if (!isset($config['dependencies'][$service])) {
        $logger->error("Missing dependency: {$service}");
        exec('composer dump-autoload'); // Attempt to fix autoload issues
    }
}

// Ensure Dependencies Are Loaded
foreach ($config['dependencies'] as $dependency) {
    if (is_callable($dependency)) {
        $dependency();
    }
}

// Output Confirmation
echo "Bootstrap process completed successfully.\n";

// Return Configurations for Application Use
return [
    'pdo' => $pdo,
    'logger' => $logger,
    'auditService' => $auditService,
    'encryptionService' => $encryptionService,
];
