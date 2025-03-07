<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\LogLevelFilter;
use App\Services\AuditService;

// Make $config available to the container - this is passed from bootstrap
global $config;

// Access the global logger variable that's set in bootstrap
global $logger;

// Step 1: Get the pre-created container from bootstrap
if (isset($GLOBALS['container']) && $GLOBALS['container'] instanceof Container) {
    $container = $GLOBALS['container'];
    $logger->info("Using pre-configured container from bootstrap.");
} else {
    $logger->critical("Container not properly initialized in bootstrap.php");
    die("Dependency Injection container failed: Container not properly initialized in bootstrap.php\n");
}

// No need to register ExceptionHandler, DatabaseHelper, and core audit services
// These should already be registered in bootstrap.php

// Include service and controller definitions with proper config passing
$svc_dep = require __DIR__ . '/svc_dep.php';
if (is_callable($svc_dep)) {
    $svc_dep($container, $config);
} else {
    $logger->error("svc_dep.php did not return a callable value.");
    die("svc_dep.php is not callable.\n");
}

$ctrl_dep = require __DIR__ . '/ctrl_dep.php';
if (is_callable($ctrl_dep)) {
    $ctrl_dep($container);
} else {
    $logger->error("ctrl_dep.php did not return a callable value.");
    die("ctrl_dep.php is not callable.\n");
}

$container->get(LoggerInterface::class)->info("Service and Controller registration completed.");

// Check for required services and circular dependencies
$requiredServices = [
    DatabaseHelper::class,
    AuditService::class,
    ExceptionHandler::class,
    // ...other required services...
];

$container->get('logger.dependencies')->info("Checking for circular dependencies...");
$failedServices = [];

foreach ($requiredServices as $service) {
    try {
        $container->get($service);
        $container->get('logger.dependencies')->info("Service loaded successfully: {$service}");
    } catch (Exception $e) {
        $errorMsg = "Service failed to load: {$service}: " . $e->getMessage();
        $container->get('logger.dependencies')->critical($errorMsg);
        $failedServices[] = $errorMsg;
    }
}

if (!empty($failedServices)) {
    die("Service failures: " . implode("\n", $failedServices) . "\n");
}

// Return key services
return [
    'db'           => $container->get(DatabaseHelper::class),
    'secure_db'    => $container->get('secure_db'),
    'logger'       => $container->get(LoggerInterface::class),
    'auditService' => $container->get(AuditService::class),
    'container'    => $container,
];
