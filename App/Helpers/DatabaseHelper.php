<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Dotenv\Dotenv;
use Exception;

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
            $dotenv->safeLoad(); // Load .env file safely (no errors if missing)
            self::$envLoaded = true;
        }
    }

    /**
     * ✅ Initialize Database Connection
     */
    private static function initializeDatabase(&$capsule, array $config, string $connectionName)
    {
        if ($capsule === null) {
            $capsule = new Capsule();
            self::loadEnv();

            try {
                $capsule->addConnection($config, $connectionName);
                $capsule->setEventDispatcher(new Dispatcher(new Container));
                $capsule->setAsGlobal();
                $capsule->bootEloquent();

                self::logEvent('database', "✅ {$connectionName} Database connected successfully.");
            } catch (Exception $e) {
                self::logEvent('errors', "❌ {$connectionName} Database connection failed: " . $e->getMessage());
                die(json_encode(["error" => "{$connectionName} database connection failed"]));
            }
        }
    }

    /**
     * ✅ Singleton: Get Main Database Instance
     */
    public static function getInstance()
    {
        self::initializeDatabase(self::$capsule, [
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? 'localhost',
            'port'      => $_ENV['DB_PORT'] ?? '3306',
            'database'  => $_ENV['DB_DATABASE'] ?? '',
            'username'  => $_ENV['DB_USERNAME'] ?? '',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ], 'default');

        return self::$capsule;
    }

    /**
     * ✅ Singleton: Get Secure Database Instance
     */
    public static function getSecureInstance()
    {
        self::initializeDatabase(self::$secureCapsule, [
            'driver'    => 'mysql',
            'host'      => $_ENV['SECURE_DB_HOST'] ?? 'localhost',
            'port'      => $_ENV['SECURE_DB_PORT'] ?? '3306',
            'database'  => $_ENV['SECURE_DB_DATABASE'] ?? '',
            'username'  => $_ENV['SECURE_DB_USERNAME'] ?? '',
            'password'  => $_ENV['SECURE_DB_PASSWORD'] ?? '',
            'charset'   => $_ENV['SECURE_DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ], 'secure');

        return self::$secureCapsule;
    }

    /**
     * ✅ Log Events
     */
    private static function logEvent($category, $message)
    {
        $logFilePath = __DIR__ . "/../../logs/{$category}.log";
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFilePath, "[$timestamp] $message\n", FILE_APPEND);
    }
}
