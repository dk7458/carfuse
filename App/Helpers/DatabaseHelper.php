<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Exception;
use App\Helpers\ApiHelper;
use function getLogger;

class DatabaseHelper
{
    private static ?DatabaseHelper $instance = null;
    private static ?DatabaseHelper $secureInstance = null;
    private Capsule $capsule;

    private function __construct(array $config)
    {
        $this->capsule = new Capsule();
        $this->capsule->addConnection($config);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    public static function getInstance(): DatabaseHelper
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';
            self::$instance = new DatabaseHelper($config['app_database']);
        }

        // Ensure connection is set before returning
        if (!self::$instance->getCapsule()->getConnection()) {
            throw new \RuntimeException("❌ Database connection [default] not configured. Check database settings.");
        }

        return self::$instance;
    }

    public static function getSecureInstance(): DatabaseHelper
    {
        if (self::$secureInstance === null) {
            $config = require __DIR__ . '/../../config/database.php';
            self::$secureInstance = new DatabaseHelper($config['secure_database']);
        }

        // Ensure connection is set before returning
        if (!self::$secureInstance->getCapsule()->getConnection()) {
            throw new \RuntimeException("❌ Secure database connection not configured. Check database settings.");
        }

        return self::$secureInstance;
    }

    public function getCapsule(): Capsule
    {
        return $this->capsule;
    }

    public function getConnection()
    {
        return $this->capsule->getConnection();
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
