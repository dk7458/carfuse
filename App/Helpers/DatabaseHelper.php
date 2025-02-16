<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * DatabaseHelper - Centralized Database Manager
 *
 * This class ensures all database interactions are handled via Eloquent ORM.
 * It initializes both `app_database` and `secure_database` connections.
 */
class DatabaseHelper
{
    private static ?Capsule $capsule = null;
    private static ?Capsule $secureCapsule = null;
    private static bool $initialized = false;
    private static ?Logger $logger = null;

    private function __construct() {}

    /**
     * ✅ Load Configuration from config/database.php
     */
    private static function loadConfig(): array
    {
        $configPath = __DIR__ . '/../../config/database.php';
        if (!file_exists($configPath)) {
            self::logEvent('errors', "❌ Database configuration file missing.");
            die(json_encode(["error" => "Database configuration file missing."]));
        }
        return require $configPath;
    }

    /**
     * ✅ Retrieve Database Configuration Dynamically
     */
    private static function getDatabaseConfig(string $key): array
    {
        $config = self::loadConfig();
        if (!isset($config[$key])) {
            self::logEvent('errors', "❌ Missing database configuration for {$key}.");
            die(json_encode(["error" => "Missing database configuration for {$key}."]));
        }
        return $config[$key];
    }

    /**
     * ✅ Initialize Database Connection Without Laravel's Event Dispatcher
     */
    private static function initializeDatabase(&$capsule, array $config, string $connectionName)
    {
        if ($capsule === null) {
            // Ensure configuration has necessary credentials
            if (
                empty($config['host']) || empty($config['database']) ||
                empty($config['username']) || empty($config['password'])
            ) {
                self::logEvent('errors', "❌ {$connectionName} Database configuration error: Missing credentials.");
                die(json_encode(["error" => "{$connectionName} database configuration error."]));
            }

            try {
                $capsule = new Capsule();
                $capsule->addConnection($config, $connectionName);
                
                // Force connection to detect errors
                $pdo = $capsule->getConnection($connectionName)->getPdo();

                if (!$pdo) {
                    throw new Exception("Could not establish PDO connection.");
                }

                if (!self::$initialized) {
                    $capsule->setAsGlobal();
                    $capsule->bootEloquent();
                    self::$initialized = true;
                }

                self::logEvent('database', "✅ {$connectionName} Database connected successfully.");
            } catch (Exception $e) {
                self::handleDatabaseError($connectionName, $e);
            }
        }
    }

    /**
     * ✅ Handle Database Connection Failures Gracefully
     */
    private static function handleDatabaseError(string $connectionName, Exception $e)
    {
        self::logEvent('errors', "❌ {$connectionName} Database connection failed: " . $e->getMessage());
        die(json_encode(["error" => "{$connectionName} database connection failed: " . $e->getMessage()]));
    }

    /**
     * ✅ Singleton: Get Main Database Instance
     */
    public static function getInstance(): Capsule
    {
        if (self::$capsule === null) {
            self::initializeDatabase(self::$capsule, self::getDatabaseConfig('app_database'), 'default');
        }
        return self::$capsule;
    }

    /**
     * ✅ Singleton: Get Secure Database Instance
     */
    public static function getSecureInstance(): Capsule
    {
        if (self::$secureCapsule === null) {
            self::initializeDatabase(self::$secureCapsule, self::getDatabaseConfig('secure_database'), 'secure');
        }
        return self::$secureCapsule;
    }

    /**
     * ✅ Log Events Using Monolog
     */
    private static function logEvent(string $category, string $message)
    {
        if (self::$logger === null) {
            self::$logger = new Logger('database');
            self::$logger->pushHandler(new StreamHandler(__DIR__ . "/../../logs/{$category}.log", Logger::DEBUG));
        }
        self::$logger->info($message);
    }
}
