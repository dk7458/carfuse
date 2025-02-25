<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ApiHelper;

class DatabaseHelper
{
    private static ?DatabaseHelper $instance = null;
    private static ?DatabaseHelper $secureInstance = null;
    private Capsule $capsule;
    private static LoggerInterface $logger;

    private function __construct(array $config)
    {
        try {
            $this->capsule = new Capsule();
            $this->capsule->addConnection($config);
            $this->capsule->setAsGlobal();
            $this->capsule->bootEloquent();

            // ✅ Log successful initialization
            self::$logger->info("✅ Database connection initialized successfully.");
        } catch (Exception $e) {
            self::$logger->critical("❌ Database connection failed: " . $e->getMessage());
            die("Database connection failed. Check logs for details.");
        }
    }

    public static function setLogger(LoggerInterface $logger)
    {
        if (!isset(self::$logger)) {
            self::$logger = $logger;
        }
    }

    private static function getDatabaseConfig(array $envConfig, string $type = 'default'): array
    {
        if ($type === 'secure') {
            return [
                'driver'    => $envConfig['SECURE_DB_DRIVER'] ?? 'mysql',
                'host'      => $envConfig['SECURE_DB_HOST'] ?? 'localhost',
                'port'      => $envConfig['SECURE_DB_PORT'] ?? '3306',
                'database'  => $envConfig['SECURE_DB_DATABASE'] ?? '',
                'username'  => $envConfig['SECURE_DB_USERNAME'] ?? '',
                'password'  => $envConfig['SECURE_DB_PASSWORD'] ?? '',
                'charset'   => $envConfig['SECURE_DB_CHARSET'] ?? 'utf8mb4',
                'collation' => $envConfig['SECURE_DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ];
        }

        return [
            'driver'    => $envConfig['DB_DRIVER'] ?? 'mysql',
            'host'      => $envConfig['DB_HOST'] ?? 'localhost',
            'port'      => $envConfig['DB_PORT'] ?? '3306',
            'database'  => $envConfig['DB_DATABASE'] ?? '',
            'username'  => $envConfig['DB_USERNAME'] ?? '',
            'password'  => $envConfig['DB_PASSWORD'] ?? '',
            'charset'   => $envConfig['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $envConfig['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ];
    }

    public static function getInstance(array $envConfig): DatabaseHelper
    {
        if (self::$instance === null) {
            try {
                self::$instance = new DatabaseHelper(self::getDatabaseConfig($envConfig, 'default'));
            } catch (Exception $e) {
                self::$logger->critical("❌ Database initialization failed: " . $e->getMessage());
                die("Database initialization failed.");
            }
        }

        return self::$instance;
    }

    public static function getSecureInstance(array $envConfig): DatabaseHelper
    {
        if (self::$secureInstance === null) {
            try {
                self::$secureInstance = new DatabaseHelper(self::getDatabaseConfig($envConfig, 'secure'));
            } catch (Exception $e) {
                self::$logger->critical("❌ Secure database initialization failed: " . $e->getMessage());
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
            self::$logger->error("❌ Failed to get database connection: " . $e->getMessage());
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
            self::$logger->error("❌ Database Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($e->getCode() == "23000") {
                return ApiHelper::sendJsonResponse('error', 'Duplicate entry error', [], 400);
            }
            return ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        } catch (\Exception $e) {
            self::$logger->error("❌ Database Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
