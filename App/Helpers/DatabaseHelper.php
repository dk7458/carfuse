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
    private static $capsule = null;
    private static $secureCapsule = null;
    private static $initialized = false;

    private function __construct() {}

    /**
     * ✅ Load Configuration from config/database.php
     */
    private static function loadConfig(): array
    {
        $configPath = __DIR__ . '/../../config/database.php';
        if (!file_exists($configPath)) {
            throw new Exception("Database configuration file missing.");
        }
        return require $configPath;
    }

    /**
     * ✅ Retrieve Database Configuration Dynamically
     */
    private static function getDatabaseConfig(string $key): array
    {
        $config = self::loadConfig();
        return $config[$key] ?? [];
    }

    /**
     * ✅ Initialize Database Connection Without Laravel's Event Dispatcher
     */
    private static function initializeDatabase(&$capsule, array $config, string $connectionName)
    {
        if ($capsule === null) {
            try {
                $capsule = new Capsule();
                $capsule->addConnection($config, $connectionName);
                // Removed Event dispatcher since we're not using Laravel events.

                // ✅ Ensure Capsule is Set as Global Once
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
        die(json_encode(["error" => "{$connectionName} database connection failed"]));
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
    private static function logEvent($category, $message)
    {
        $logFilePath = __DIR__ . "/../../logs/{$category}.log";
        $log = new Logger('database');
        $log->pushHandler(new StreamHandler($logFilePath, Logger::DEBUG));
        $log->info($message);
    }
}
