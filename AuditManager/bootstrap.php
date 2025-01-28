<?php

// Bootstrap file for Audit Manager module
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use AuditManager\Services\AuditService;
use AuditManager\Middleware\AuditTrailMiddleware;
use Psr\Log\LoggerInterface;

// Initialize database connection
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Connected to the database successfully.\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit(1);
}

// Initialize logger (assuming Monolog or similar is being used)
$logger = new Monolog\Logger('audit_manager');
$logger->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/../logs/audit_manager.log', Monolog\Logger::INFO));

// Initialize AuditService
$auditService = new AuditService($pdo);

// Initialize middleware
$auditMiddleware = new AuditTrailMiddleware($auditService, $logger);

// Register middleware globally (example)
$router->middleware($auditMiddleware);

// Configuration (optional)
$config = [
    'audit_log_file' => __DIR__ . '/../logs/audit_manager.log',
    'audit_log_level' => Monolog\Logger::INFO,
];

// Print confirmation
echo "Audit Manager bootstrap completed.\n";
