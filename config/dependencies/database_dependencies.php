<?php

/**
 * Database Dependencies Configuration
 * 
 * This file contains all database-related dependency registrations for the DI container.
 * It centralizes the database configuration to make the main dependencies.php cleaner.
 */

use DI\Container;
use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;

/**
 * Register database dependencies in the container
 * 
 * @param Container $container The DI container
 * @param array $config Database configuration
 * @return Container The container with registered database dependencies
 * @throws Exception When database initialization fails
 */
function registerDatabases(Container $container, array $config): Container
{
    // Get database logger from container
    $dbLogger = $container->get('db_logger');
    $systemLogger = $container->get(LoggerInterface::class);
    
    try {
        // 1. Set the logger for DatabaseHelper
        DatabaseHelper::setLogger($dbLogger);
        
        // 2. Initialize the app database instance
        if (!isset($config['app_database']) || !is_array($config['app_database'])) {
            throw new Exception("App database configuration missing or invalid");
        }
        $database = DatabaseHelper::getInstance($config['app_database']);
        $dbLogger->info("✅ App database initialized successfully");
        
        // 3. Initialize the secure database instance for sensitive operations
        if (!isset($config['secure_database']) || !is_array($config['secure_database'])) {
            throw new Exception("Secure database configuration missing or invalid");
        }
        $secureDatabase = DatabaseHelper::getSecureInstance($config['secure_database']);
        $dbLogger->info("✅ Secure database initialized successfully");
        
        // 4. Register the app database instance as the default DatabaseHelper
        $container->set(DatabaseHelper::class, fn() => $database);
        
        // 5. Register database instances in container
        $container->set('db', fn() => $database); // Generic key for app database
        $container->set('secure_db', fn() => $secureDatabase);
        
        // 6. Verify app database connection
        verifyDatabaseConnection($database, $dbLogger, 'App');
        
        // 7. Verify secure database connection
        verifyDatabaseConnection($secureDatabase, $dbLogger, 'Secure');
        
        return $container;
    } catch (Exception $e) {
        $dbLogger->error("❌ Database initialization failed: " . $e->getMessage());
        $systemLogger->critical("Database initialization failed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw new Exception("Database initialization failed: " . $e->getMessage());
    }
}

/**
 * Verify a database connection by attempting to get a PDO instance
 * 
 * @param DatabaseHelper $db Database helper instance
 * @param LoggerInterface $logger Logger for recording results
 * @param string $type Type of database (for logging)
 * @return bool True if connection is valid
 * @throws Exception When connection fails
 */
function verifyDatabaseConnection(DatabaseHelper $db, LoggerInterface $logger, string $type = 'Database'): bool
{
    try {
        $pdo = $db->getConnection()->getPdo();
        if (!$pdo) {
            throw new Exception("Failed to get PDO instance");
        }
        
        // Try a simple query to verify connection is working
        $pdo->query('SELECT 1');
        
        $logger->info("✅ {$type} database connection verified successfully");
        return true;
    } catch (Exception $e) {
        $logger->critical("❌ {$type} database connection failed: " . $e->getMessage());
        throw new Exception("{$type} database connection failed: " . $e->getMessage());
    }
}

/**
 * Get database configuration from a file
 * 
 * @param string $configPath Path to the database config file
 * @return array Database configuration
 * @throws Exception When config file doesn't exist or has invalid structure
 */
function getDatabaseConfig(string $configPath): array
{
    if (!file_exists($configPath)) {
        throw new Exception("Database configuration file not found: {$configPath}");
    }
    
    $config = require $configPath;
    
    if (!is_array($config) || !isset($config['app_database']) || !isset($config['secure_database'])) {
        throw new Exception("Invalid database configuration structure");
    }
    
    return $config;
}

/**
 * Initialize Eloquent ORM for a database
 * 
 * @param DatabaseHelper $dbHelper Database helper instance
 * @return void
 */
function initializeEloquent(DatabaseHelper $dbHelper): void
{
    $dbHelper->bootEloquent();
}

// If this file is included directly, return a container with registered databases
if (!isset($container) && basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    $container = new Container();
    
    // Simple logger for standalone mode
    $container->set('db_logger', function() {
        $logger = new \Monolog\Logger('database');
        $handler = new \Monolog\Handler\StreamHandler('php://stderr');
        $logger->pushHandler($handler);
        return $logger;
    });
    
    $container->set(LoggerInterface::class, $container->get('db_logger'));
    
    // Get database config
    $config = getDatabaseConfig(__DIR__ . '/database.php');
    
    // Register databases
    $container = registerDatabases($container, $config);
    
    return $container;
}
