<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\SecurityHelper;
use App\Helpers\LogLevelFilter;
use App\Services\AuditService;

// Access global variables - these should be pre-initialized in bootstrap.php
global $logger, $loggers, $config, $container;

// Ensure critical global variables exist
if (!isset($logger)) {
    die("Fatal error: Global logger not initialized in bootstrap.php\n");
}

if (!isset($config) || !is_array($config)) {
    $logger->critical("Configuration not properly loaded");
    die("Fatal error: Configuration not properly loaded\n");
}

// Step 1: Get the pre-created container from bootstrap
if (isset($container) && $container instanceof Container) {
    $logger->info("Using pre-configured container from bootstrap");
} else {
    $logger->critical("Container not properly initialized in bootstrap.php");
    // Create a new container if not available (fallback scenario)
    $container = new Container();
    $logger->warning("Created new container as fallback - services may be missing");
}

// Step 2: Register main logger and category-specific loggers
$container->set(LoggerInterface::class, function() use ($logger) {
    return $logger;
});

// Register all category-specific loggers from global $loggers array
if (isset($loggers) && is_array($loggers)) {
    foreach ($loggers as $category => $categoryLogger) {
        $container->set("logger.{$category}", function() use ($categoryLogger) {
            return $categoryLogger;
        });
        $logger->debug("Registered logger.{$category}");
    }
} else {
    $logger->warning("Global \$loggers array not found - category-specific logging unavailable");
}

// Step 3: Ensure core services are registered if not already

// Core helpers
if (!$container->has(ExceptionHandler::class)) {
    $container->set(ExceptionHandler::class, function() use ($logger) {
        return new ExceptionHandler($logger);
    });
    $logger->info("Registered ExceptionHandler");
}

if (!$container->has(SecurityHelper::class)) {
    $container->set(SecurityHelper::class, function() use ($logger, $config) {
        return new SecurityHelper(
            $logger, 
            $config['security'] ?? []
        );
    });
    $logger->info("Registered SecurityHelper");
}

if (!$container->has(DatabaseHelper::class)) {
    $container->set(DatabaseHelper::class, function() use ($logger, $config) {
        return new DatabaseHelper(
            $config['database'] ?? [],
            $logger
        );
    });
    $logger->info("Registered DatabaseHelper");
}

// Also register the standard DB instance specifically for easier access
if (!$container->has('db')) {
    $container->set('db', function($c) {
        return $c->get(DatabaseHelper::class);
    });
}

// Register secure DB connection if not already registered
if (!$container->has('secure_db')) {
    $container->set('secure_db', function() use ($logger, $config) {
        return new DatabaseHelper(
            $config['secure_database'] ?? $config['database'] ?? [],
            $logger,
            true // Use secure connection
        );
    });
    $logger->info("Registered secure database connection");
}

// Register LogLevelFilter if not already registered
if (!$container->has(LogLevelFilter::class)) {
    $container->set(LogLevelFilter::class, function() use ($config) {
        return new LogLevelFilter($config['logging']['levels'] ?? []);
    });
}

// Step 4: Include service and controller definitions with proper config passing
$svcDepPath = __DIR__ . '/svc_dep.php';
if (file_exists($svcDepPath)) {
    $svc_dep = require $svcDepPath;
    if (is_callable($svc_dep)) {
        $svc_dep($container, $config);
        $logger->info("Service dependencies loaded successfully");
    } else {
        $logger->error("svc_dep.php did not return a callable value");
        die("Fatal error: svc_dep.php is not callable\n");
    }
} else {
    $logger->error("svc_dep.php not found at {$svcDepPath}");
    die("Fatal error: svc_dep.php not found\n");
}

$ctrlDepPath = __DIR__ . '/ctrl_dep.php';
if (file_exists($ctrlDepPath)) {
    $ctrl_dep = require $ctrlDepPath;
    if (is_callable($ctrl_dep)) {
        $ctrl_dep($container);
        $logger->info("Controller dependencies loaded successfully");
    } else {
        $logger->error("ctrl_dep.php did not return a callable value");
        die("Fatal error: ctrl_dep.php is not callable\n");
    }
} else {
    $logger->error("ctrl_dep.php not found at {$ctrlDepPath}");
    die("Fatal error: ctrl_dep.php not found\n");
}

// Step 5: Check for required services and circular dependencies
$requiredServices = [
    DatabaseHelper::class,
    ExceptionHandler::class,
    SecurityHelper::class,
    AuditService::class,
    LoggerInterface::class,
    // Add other critical services here
];

$container->get('logger.dependencies')->info("Checking core services...");
$failedServices = [];

foreach ($requiredServices as $service) {
    try {
        $container->get($service);
        $container->get('logger.dependencies')->debug("Service loaded successfully: {$service}");
    } catch (\Exception $e) {
        $errorMsg = "Service failed to load: {$service}: " . $e->getMessage();
        $container->get('logger.dependencies')->critical($errorMsg);
        $failedServices[] = $errorMsg;
    }
}

if (!empty($failedServices)) {
    $errorMessages = implode("\n", $failedServices);
    $logger->critical("Core service initialization failures: {$errorMessages}");
    die("Fatal error: Core service failures detected\n{$errorMessages}\n");
}

// Return key services for use in the application
return [
    'db'           => $container->get(DatabaseHelper::class),
    'secure_db'    => $container->get('secure_db'),
    'logger'       => $container->get(LoggerInterface::class),
    'auditService' => $container->get(AuditService::class),
    'security'     => $container->get(SecurityHelper::class),
    'container'    => $container,
];
