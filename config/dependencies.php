<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use DI\Container;
use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\SecurityHelper;
use App\Helpers\LogLevelFilter;
use App\Helpers\ApiHelper;
use App\Helpers\LoggingHelper;
use App\Helpers\LogQueryBuilder;
use App\Helpers\SetupHelper;
use App\Helpers\ViewHelper;
use App\Services\AuditService;
use App\Middleware\TokenValidationMiddleware;
use App\Middleware\SessionMiddleware;
use App\Middleware\RequireAuthMiddleware;
use App\Middleware\EncryptionMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\LoggingMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Services\EncryptionService;
use App\Services\Auth\TokenService;
use App\Services\RateLimiter;

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

// Step 1: Get or create the container with proper caching
if (isset($container) && $container instanceof Container) {
    $logger->info("Using pre-configured container from bootstrap");
} else {
    $logger->warning("Container not found in bootstrap - creating new container");
    
    // Create new container with compilation for production
    $containerBuilder = new ContainerBuilder();
    
    // Enable container compilation for production
    if (isset($config['environment']) && $config['environment'] === 'production') {
        $cacheDir = __DIR__ . '/../var/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $containerBuilder->enableCompilation($cacheDir);
        $containerBuilder->writeProxiesToFile(true, $cacheDir . '/proxies');
        $logger->info("Container compilation enabled for production environment");
    }
    
    try {
        $container = $containerBuilder->build();
        $logger->info("Created new DI container");
    } catch (\Exception $e) {
        $logger->critical("Failed to build container: " . $e->getMessage());
        die("Fatal error: Container initialization failed\n");
    }
}

// Step 2: Register loggers
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

// Step 3: Register all helper classes based on constructor_signatures.md

// ApiHelper
$container->set(ApiHelper::class, function(Container $c) {
    $logger = $c->get('logger.api') ?? $c->get(LoggerInterface::class);
    $logFile = $config['logging']['api_log'] ?? null;
    return new ApiHelper($logger, $logFile);
});

// DatabaseHelper with updated constructor
$container->set(DatabaseHelper::class, function(Container $c) use ($config) {
    $logger = $c->get('logger.db') ?? $c->get(LoggerInterface::class);
    $apiHelper = $c->get(ApiHelper::class);
    return new DatabaseHelper($config['database'] ?? [], $logger, $apiHelper);
});

// Register standard DB instance for easier access
$container->set('db', function(Container $c) {
    return $c->get(DatabaseHelper::class);
});

// Register secure DB connection
$container->set('secure_db', function(Container $c) use ($config, $logger) {
    $apiHelper = $c->get(ApiHelper::class);
    return new DatabaseHelper(
        $config['secure_database'] ?? $config['database'] ?? [],
        $logger,
        $apiHelper,
        true // Use secure connection
    );
});

// ExceptionHandler with category-specific loggers
$container->set(ExceptionHandler::class, function(Container $c) {
    $dbLogger = $c->get('logger.db') ?? $c->get(LoggerInterface::class);
    $authLogger = $c->get('logger.auth') ?? $c->get(LoggerInterface::class);
    $systemLogger = $c->get('logger.system') ?? $c->get(LoggerInterface::class);
    
    return new ExceptionHandler($dbLogger, $authLogger, $systemLogger);
});

// LoggingHelper
$container->set(LoggingHelper::class, function(Container $c) use ($loggers) {
    $defaultLogger = $c->get(LoggerInterface::class);
    return new LoggingHelper($defaultLogger, $loggers ?? []);
});

// LogLevelFilter
$container->set(LogLevelFilter::class, function() use ($config) {
    $minLevel = $config['logging']['min_level'] ?? 'debug';
    return new LogLevelFilter($minLevel);
});

// LogQueryBuilder
$container->set(LogQueryBuilder::class, function(Container $c) {
    return new LogQueryBuilder($c->get(SecurityHelper::class));
});

// SecurityHelper
$container->set(SecurityHelper::class, function(Container $c) use ($config) {
    $logger = $c->get('logger.security') ?? $c->get(LoggerInterface::class);
    $logFile = $config['logging']['security_log'] ?? null;
    return new SecurityHelper($logger, $logFile);
});

// SetupHelper
$container->set(SetupHelper::class, function(Container $c) {
    return new SetupHelper(
        $c->get(DatabaseHelper::class),
        $c->get('logger.setup') ?? $c->get(LoggerInterface::class)
    );
});

// ViewHelper
$container->set(ViewHelper::class, function(Container $c) {
    return new ViewHelper();
});

// Step 4: Register middleware components

// TokenValidationMiddleware
$container->set(TokenValidationMiddleware::class, function(Container $c) {
    return new TokenValidationMiddleware(
        $c->get('App\Services\Auth\AuthService'),
        $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
        $c->get(TokenService::class)
    );
});

// SessionMiddleware
$container->set(SessionMiddleware::class, function(Container $c) use ($config) {
    return new SessionMiddleware(
        $c->get('logger.session') ?? $c->get(LoggerInterface::class),
        $config['session'] ?? []
    );
});

// RequireAuthMiddleware
$container->set(RequireAuthMiddleware::class, function(Container $c) {
    return new RequireAuthMiddleware(
        $c->get('logger.auth') ?? $c->get(LoggerInterface::class)
    );
});

// EncryptionMiddleware
$container->set(EncryptionMiddleware::class, function(Container $c) {
    return new EncryptionMiddleware(
        $c->get('logger.security') ?? $c->get(LoggerInterface::class),
        $c->get(EncryptionService::class)
    );
});

// AuthMiddleware
$container->set(AuthMiddleware::class, function(Container $c) {
    return new AuthMiddleware(
        $c->get(TokenService::class),
        $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
        $c->get(DatabaseHelper::class),
        false // Default not required
    );
});

// RateLimitMiddleware
$container->set(RateLimitMiddleware::class, function(Container $c) {
    return new RateLimitMiddleware(
        $c->get(RateLimiter::class),
        $c->get('logger.security') ?? $c->get(LoggerInterface::class)
    );
});

// LoggingMiddleware
$container->set(LoggingMiddleware::class, function(Container $c) {
    return new LoggingMiddleware(
        $c->get(LoggerInterface::class),
        $c->get(AuditService::class)
    );
});

// SecurityHeadersMiddleware
$container->set(SecurityHeadersMiddleware::class, function(Container $c) use ($config) {
    return new SecurityHeadersMiddleware(
        $c->get('logger.security') ?? $c->get(LoggerInterface::class),
        $config['security']['headers'] ?? []
    );
});

// Step 5: Include service and controller definitions
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

// Step 6: Check for required services and circular dependencies
$requiredServices = [
    DatabaseHelper::class,
    ExceptionHandler::class,
    SecurityHelper::class,
    AuditService::class,
    LoggerInterface::class,
    // Add middleware to required services
    AuthMiddleware::class,
    RateLimitMiddleware::class,
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
