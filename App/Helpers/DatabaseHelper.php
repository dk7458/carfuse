<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;
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
    private static $envLoaded = false;

    /**
     * ✅ Private Constructor - Prevent Direct Instantiation (Singleton)
     */
    private function __construct() {}

    /**
     * ✅ Load Environment Variables
     */
    private static function loadEnv()
    {
        if (!self::$envLoaded) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad(); // Load .env safely (no errors if missing)
            self::$envLoaded = true;
        }
    }

    /**
     * ✅ Retrieve Database Configuration Dynamically
     */
    private static function getDatabaseConfig($prefix)
    {
        return [
            'driver'    => 'mysql',
            'host'      => $_ENV["{$prefix}_DB_HOST"] ?? 'localhost',
            'port'      => $_ENV["{$prefix}_DB_PORT"] ?? '3306',
            'database'  => $_ENV["{$prefix}_DB_DATABASE"] ?? '',
            'username'  => $_ENV["{$prefix}_DB_USERNAME"] ?? '',
            'password'  => $_ENV["{$prefix}_DB_PASSWORD"] ?? '',
            'charset'   => $_ENV["{$prefix}_DB_CHARSET"] ?? 'utf8mb4',
            'collation' => $_ENV["{$prefix}_DB_COLLATION"] ?? 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ];
    }

    /**
     * ✅ Initialize Database Connection Without Laravel's Event Dispatcher
     */
    private static function initializeDatabase(&$capsule, array $config, string $connectionName)
    {
        if ($capsule === null) {
            self::loadEnv();

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
            self::initializeDatabase(self::$capsule, self::getDatabaseConfig('DB'), 'default');
        }

        return self::$capsule;
    }

    /**
     * ✅ Singleton: Get Secure Database Instance
     */
    public static function getSecureInstance(): Capsule
    {
        if (self::$secureCapsule === null) {
            self::initializeDatabase(self::$secureCapsule, self::getDatabaseConfig('SECURE_DB'), 'secure');
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
