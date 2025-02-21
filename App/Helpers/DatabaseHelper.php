<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Dotenv\Dotenv;
use Exception;
use App\Helpers\ApiHelper;
use function getLogger;

class DatabaseHelper
{
    private static ?DatabaseHelper $instance = null;
    private static ?DatabaseHelper $secureInstance = null;
    private Capsule $capsule;
    private static bool $envLoaded = false;

    private function __construct(array $config)
    {
        try {
            $this->capsule = new Capsule();
            $this->capsule->addConnection($config);
            $this->capsule->setAsGlobal();
            $this->capsule->bootEloquent();

            // ✅ Log successful initialization
            getLogger('db')->info("✅ Database connection initialized successfully.");
        } catch (Exception $e) {
            getLogger('db')->critical("❌ Database connection failed: " . $e->getMessage());
            die("Database connection failed. Check logs for details.");
        }
    }

    private static function loadEnv()
    {
        if (!self::$envLoaded) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
            self::$envLoaded = true;
        }
    }

    public static function getInstance(): DatabaseHelper
    {
        if (self::$instance === null) {
            self::loadEnv();
            try {
                $config = require __DIR__ . '/../../config/database.php';
                self::$instance = new DatabaseHelper($config['app_database']);
            } catch (Exception $e) {
                getLogger('db')->critical("❌ Database initialization failed: " . $e->getMessage());
                die("Database initialization failed.");
            }
        }

        return self::$instance;
    }

    public static function getSecureInstance(): DatabaseHelper
    {
        if (self::$secureInstance === null) {
            self::loadEnv();
            try {
                $config = require __DIR__ . '/../../config/database.php';
                self::$secureInstance = new DatabaseHelper($config['secure_database']);
            } catch (Exception $e) {
                getLogger('db')->critical("❌ Secure database initialization failed: " . $e->getMessage());
                die("Secure database initialization failed.");
            }
        }

        return self::$secureInstance;
    }

    public function getCapsule(): Capsule
    {
        return $this->capsule;
    }

    public function getConnection()
    {
        try {
            return $this->capsule->getConnection();
        } catch (Exception $e) {
            getLogger('db')->error("❌ Failed to get database connection: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Safe Query Execution with Exception Handling
     */
    public static function safeQuery(callable $query)
    {
        try {
            return $query(self::getInstance()->getCapsule());
        } catch (\PDOException $e) {
            getLogger('db')->error("❌ Database Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($e->getCode() == "23000") {
                return ApiHelper::sendJsonResponse('error', 'Duplicate entry error', [], 400);
            }
            return ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        } catch (\Exception $e) {
            getLogger('db')->error("❌ Database Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        }
    }

    /**
     * ✅ Wrapper for Insert Queries
     */
    public static function insert($table, $data)
    {
        return self::safeQuery(fn ($db) => $db->table($table)->insertGetId($data));
    }

    /**
     * ✅ Wrapper for Update Queries
     */
    public static function update($table, $data, $where)
    {
        return self::safeQuery(fn ($db) => $db->table($table)->where($where)->update($data));
    }

    /**
     * ✅ Wrapper for Delete Queries
     */
    public static function delete($table, $where)
    {
        return self::safeQuery(fn ($db) => $db->table($table)->where($where)->delete());
    }

    /**
     * ✅ Wrapper for Select Queries
     */
    public static function select($query, $params = [])
    {
        return self::safeQuery(fn ($db) => $db->select($query, $params));
    }
}
