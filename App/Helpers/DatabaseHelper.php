<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Dotenv\Dotenv;
use Exception;
use App\Helpers\ApiHelper;
// Removed: use Psr\Log\LoggerInterface;
// Added dynamic logger usage:
use function getLogger;

class DatabaseHelper
{
    private static $capsule = null;
    private static $secureCapsule = null;
    private static $initialized = false;
    private static $envLoaded = false;
    
    // ...existing code...

    // Removed setLogger() method

    private static function loadEnv()
    {
        if (!self::$envLoaded) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad();
            self::$envLoaded = true;
        }
    }

    private static function initializeDatabase(&$capsule, array $config, string $connectionName)
    {
        if ($capsule === null) {
            self::loadEnv();

            try {
                $capsule = new Capsule();
                $capsule->addConnection($config, $connectionName);
                $capsule->setEventDispatcher(new Dispatcher(new Container));

                $pdo = $capsule->getConnection($connectionName)->getPdo();
                if (!$pdo) {
                    throw new Exception("Failed to obtain PDO connection.");
                }

                if (!self::$initialized) {
                    $capsule->setAsGlobal();
                    $capsule->bootEloquent();
                    self::$initialized = true;
                }

                // Log successful connection with proper category
                getLogger('db')->info("✅ {$connectionName} Database connected successfully");
            } catch (Exception $e) {
                getLogger('db')->error("❌ {$connectionName} Database connection failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                die("{$connectionName} database connection failed");
            }
        }
    }

    private static function handleDatabaseError(string $connectionName, Exception $e)
    {
        getLogger('db')->error("❌ {$connectionName} Database connection error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        die("{$connectionName} database connection failed");
    }

    public static function getInstance(): Capsule
    {
        if (self::$capsule === null) {
            self::initializeDatabase(self::$capsule, [
                'driver'    => 'mysql',
                'host'      => $_ENV['DB_HOST'] ?? 'localhost',
                'port'      => $_ENV['DB_PORT'] ?? '3306',
                'database'  => $_ENV['DB_DATABASE'] ?? '',
                'username'  => $_ENV['DB_USERNAME'] ?? '',
                'password'  => $_ENV['DB_PASSWORD'] ?? '',
                'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ], 'default');
        }

        return self::$capsule;
    }

    public static function getSecureInstance(): Capsule
    {
        if (self::$secureCapsule === null) {
            self::initializeDatabase(self::$secureCapsule, [
                'driver'    => 'mysql',
                'host'      => $_ENV['SECURE_DB_HOST'] ?? 'localhost',
                'port'      => $_ENV['SECURE_DB_PORT'] ?? '3306',
                'database'  => $_ENV['SECURE_DB_DATABASE'] ?? '',
                'username'  => $_ENV['SECURE_DB_USERNAME'] ?? '',
                'password'  => $_ENV['SECURE_DB_PASSWORD'] ?? '',
                'charset'   => $_ENV['SECURE_DB_CHARSET'] ?? 'utf8mb4',
                'collation' => $_ENV['SECURE_DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ], 'secure');
        }

        return self::$secureCapsule;
    }

    // Removed logEvent() wrapper in favor of direct getLogger() calls

    /**
     * ✅ Safe Query Execution with Exception Handling
     */
    public static function safeQuery(callable $query)
    {
        try {
            return $query(self::getInstance());
        } catch (\PDOException $e) {
            getLogger('db')->error("❌ Database Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($e->getCode() == "23000") {
                ApiHelper::sendJsonResponse('error', 'Duplicate entry error', [], 400);
            }

            ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        } catch (\Exception $e) {
            getLogger('db')->error("❌ Database Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        }
    }

    /**
     * ✅ Wrapper for Insert Queries
     */
    public static function insert($table, $data)
    {
        return self::safeQuery(function ($db) use ($table, $data) {
            return $db->table($table)->insertGetId($data);
        });
    }

    /**
     * ✅ Wrapper for Update Queries
     */
    public static function update($table, $data, $where)
    {
        return self::safeQuery(function ($db) use ($table, $data, $where) {
            return $db->table($table)->where($where)->update($data);
        });
    }

    /**
     * ✅ Wrapper for Delete Queries
     */
    public static function delete($table, $where)
    {
        return self::safeQuery(function ($db) use ($table, $where) {
            return $db->table($table)->where($where)->delete();
        });
    }

    /**
     * ✅ Wrapper for Select Queries
     */
    public static function select($query, $params = [])
    {
        return self::safeQuery(function ($db) use ($query, $params) {
            return $db->select($query, $params);
        });
    }
}
