<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\SecurityHelper;
use App\Services\Validator;
use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
use App\Services\UserService;
use App\Controllers\AuthController;
use App\Controllers\UserController;

// Step 1: Initialize DI Container
try {
    $container = new Container();
    
    // Configure loggers
    $container->set(LoggerInterface::class, function() {
        $logger = new Logger('system');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/system.log', Logger::INFO));
        return $logger;
    });
    
    $container->set('authLogger', function() {
        $logger = new Logger('auth');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/auth.log', Logger::INFO));
        return $logger;
    });
    
    $container->set('userLogger', function() {
        $logger = new Logger('user');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/user.log', Logger::INFO));
        return $logger;
    });
    
    $container->set('auditLogger', function() {
        $logger = new Logger('audit');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/audit.log', Logger::INFO));
        return $logger;
    });
    
    $container->get(LoggerInterface::class)->info("DI Container created and loggers registered.");
} catch (Exception $e) {
    die("Dependency Injection container initialization failed: " . $e->getMessage());
}

// Step 2: Load configuration
$config = [
    'database' => require __DIR__ . '/database.php',
    'encryption' => require __DIR__ . '/encryption.php',
];

// Step 3: Register ExceptionHandler
$container->set(ExceptionHandler::class, function($c) {
    return new ExceptionHandler(
        $c->get(LoggerInterface::class)
    );
});

// Step 4: Configure DatabaseHelper
try {
    $container->set(DatabaseHelper::class, function($c) use ($config) {
        $dbHelper = new DatabaseHelper();
        $dbHelper->addConnection([
            'driver'    => 'mysql',
            'host'      => $config['database']['app_database']['host'],
            'database'  => $config['database']['app_database']['database'],
            'username'  => $config['database']['app_database']['username'],
            'password'  => $config['database']['app_database']['password'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ], 'app_database');
        
        $dbHelper->bootEloquent();
        return $dbHelper;
    });
    
    // Test database connection
    $db = $container->get(DatabaseHelper::class);
    $container->get(LoggerInterface::class)->info("Database connection established successfully");
} catch (Exception $e) {
    $container->get(LoggerInterface::class)->critical("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}

// Step 5: Register SecurityHelper
$container->set(SecurityHelper::class, function() {
    return new SecurityHelper();
});

// Step 6: Register Validator
$container->set(Validator::class, function($c) {
    return new Validator();
});

// Step 7: Register TokenService
$container->set(TokenService::class, function($c) use ($config) {
    return new TokenService(
        $config['encryption']['jwt_secret'],
        3600, // JWT TTL: 1 hour
        604800, // Refresh Token TTL: 7 days
        $c->get('authLogger'),
        $c->get(ExceptionHandler::class),
        $c->get(DatabaseHelper::class)
    );
});

// Step 8: Register AuthService
$container->set(AuthService::class, function($c) use ($config) {
    return new AuthService(
        $c->get(DatabaseHelper::class),
        $c->get(TokenService::class),
        $c->get(ExceptionHandler::class),
        $c->get('authLogger'),
        $c->get('auditLogger'),
        $config['encryption'],
        $c->get(Validator::class)
    );
});

// Step 9: Register UserService
$container->set(UserService::class, function($c) {
    return new UserService(
        $c->get(DatabaseHelper::class),
        $c->get('userLogger'),
        $c->get('auditLogger'),
        $c->get(ExceptionHandler::class),
        $c->get(Validator::class)
    );
});

// Step 10: Register Controllers
$container->set(AuthController::class, function($c) {
    return new AuthController(
        $c->get(AuthService::class),
        $c->get(TokenService::class),
        $c->get(ExceptionHandler::class),
        $c->get('authLogger'),
        $c->get('auditLogger')
    );
});

$container->set(UserController::class, function($c) {
    return new UserController(
        $c->get(UserService::class),
        $c->get(TokenService::class),
        $c->get(ExceptionHandler::class),
        $c->get('userLogger'),
        $c->get('auditLogger')
    );
});

// Verify key services were registered successfully
try {
    $container->get(AuthService::class);
    $container->get(UserService::class);
    $container->get(TokenService::class);
    $container->get(LoggerInterface::class)->info("All services loaded successfully");
} catch (Exception $e) {
    $container->get(LoggerInterface::class)->critical("Service initialization failed: " . $e->getMessage());
    die("Service initialization failed: " . $e->getMessage());
}

return $container;
