<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Dotenv\Dotenv;
use Exception;

class DatabaseHelper
{
    private static $capsule = null;
    private static $secureCapsule = null;

    private function __construct() {}

    // ✅ Load Environment Variables
    private static function loadEnv()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad(); // Load .env file safely (no errors if missing)
    }

    // ✅ Singleton: Get Main Database Instance
    public static function getInstance()
    {
        if (self::$capsule === null) {
            self::$capsule = new Capsule;
            self::loadEnv();

            try {
                self::$capsule->addConnection([
                    'driver'    => 'mysql',
                    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
                    'port'      => $_ENV['DB_PORT'] ?? '3306',
                    'database'  => $_ENV['DB_DATABASE'] ?? '',
                    'username'  => $_ENV['DB_USERNAME'] ?? '',
                    'password'  => $_ENV['DB_PASSWORD'] ?? '',
                    'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix'    => '',
                ]);

                self::$capsule->setEventDispatcher(new Dispatcher(new Container));
                self::$capsule->setAsGlobal();
                self::$capsule->bootEloquent();

                self::logEvent('database', "✅ Main Database connected successfully.");
            } catch (Exception $e) {
                self::logEvent('errors', "❌ Database connection failed: " . $e->getMessage());
                die(json_encode(["error" => "Database connection failed"]));
            }
        }

        return self::$capsule;
    }

    // ✅ Singleton: Get Secure Database Instance
    public static function getSecureInstance()
    {
        if (self::$secureCapsule === null) {
            self::$secureCapsule = new Capsule;
            self::loadEnv();

            try {
                self::$secureCapsule->addConnection([
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

                self::$secureCapsule->setEventDispatcher(new Dispatcher(new Container));
                self::$secureCapsule->setAsGlobal();
                self::$secureCapsule->bootEloquent();

                self::logEvent('database', "✅ Secure Database connected successfully.");
            } catch (Exception $e) {
                self::logEvent('errors', "❌ Secure Database connection failed: " . $e->getMessage());
                die(json_encode(["error" => "Secure database connection failed"]));
            }
        }

        return self::$secureCapsule;
    }

    // ✅ Log Events
    private static function logEvent($category, $message)
    {
        $logFilePath = __DIR__ . "/../../logs/{$category}.log";
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFilePath, "[$timestamp] $message\n", FILE_APPEND);
    }
}
