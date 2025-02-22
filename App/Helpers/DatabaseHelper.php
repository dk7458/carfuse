<?php

namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
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

    private static function getDatabaseConfig(string $type = 'default'): array
    {
        if ($type === 'secure') {
            return [
                'driver'    => $_ENV['SECURE_DB_DRIVER'] ?? 'mysql',
                'host'      => $_ENV['SECURE_DB_HOST'] ?? 'localhost',
                'port'      => $_ENV['SECURE_DB_PORT'] ?? '3306',
                'database'  => $_ENV['SECURE_DB_DATABASE'] ?? '',
                'username'  => $_ENV['SECURE_DB_USERNAME'] ?? '',
                'password'  => $_ENV['SECURE_DB_PASSWORD'] ?? '',
                'charset'   => $_ENV['SECURE_DB_CHARSET'] ?? 'utf8mb4',
                'collation' => $_ENV['SECURE_DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ];
        }

        return [
            'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? 'localhost',
            'port'      => $_ENV['DB_PORT'] ?? '3306',
            'database'  => $_ENV['DB_DATABASE'] ?? '',
            'username'  => $_ENV['DB_USERNAME'] ?? '',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ];
    }

    public static function getInstance(): DatabaseHelper
    {
        if (self::$instance === null) {
            try {
                self::$instance = new DatabaseHelper(self::getDatabaseConfig('default'));
            } catch (Exception $e) {
                self::$logger->critical("❌ Database initialization failed: " . $e->getMessage());
                die("Database initialization failed.");
            }
        }

        return self::$instance;
    }

    public static function getSecureInstance(): DatabaseHelper
    {
        if (self::$secureInstance === null) {
            try {
                self::$secureInstance = new DatabaseHelper(self::getDatabaseConfig('secure'));
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
