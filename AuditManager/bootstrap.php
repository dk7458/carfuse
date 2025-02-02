<?php

/**
 * Bootstrap file for Audit Manager module
 * Path: audit_manager/bootstrap.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use AuditManager\Services\AuditService;
use AuditManager\Middleware\AuditTrailMiddleware;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

// Load database configuration
$dbConfig = require __DIR__ . '/../config/database.php';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', 
    $dbConfig['host'], 
    $dbConfig['port'], 
    $dbConfig['database'], 
    $dbConfig['charset']
);

try {
    // Initialize database connection
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connected to the database successfully.\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit(1);
}

// Initialize logger
$logFilePath = __DIR__ . '/../logs/audit_manager.log';
$logger = new Logger('audit_manager');
$streamHandler = new StreamHandler($logFilePath, Logger::DEBUG);
$streamHandler->setFormatter(new LineFormatter(null, null, true, true));
$logger->pushHandler($streamHandler);

// Initialize AuditService
$auditService = new AuditService($pdo);

// Initialize middleware
$auditMiddleware = new AuditTrailMiddleware($auditService, $logger);

// Register middleware globally (example, depends on framework/router used)
if (isset($router)) {
    $router->middleware($auditMiddleware);
}

// Optional: Print confirmation
echo "Audit Manager bootstrap completed.\n";
