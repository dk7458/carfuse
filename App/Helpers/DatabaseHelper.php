<?php

namespace App\Helpers;

use PDO;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ApiHelper;

class DatabaseHelper
{
    private static ?DatabaseHelper $instance = null;
    private static ?DatabaseHelper $secureInstance = null;
    private PDO $pdo;
    private static LoggerInterface $logger;

    private function __construct(array $config)
    {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

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
        $config = require __DIR__ . '/../../config/database.php';
        return $type === 'secure' ? $config['secure_database'] : $config['app_database'];
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
                error_log("[DEBUG] Initializing Secure Database", 3, __DIR__ . "/debug.log"); // Ensure log file is writable
            } catch (Exception $e) {
                self::$logger->critical("❌ Secure database initialization failed: " . $e->getMessage());
                die("Secure database initialization failed.");
            }
        }

        return self::$secureInstance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getConnection()
    {
        try {
            return $this->pdo;
        } catch (Exception $e) {
            if (self::$logger) {
                self::$logger->error("❌ Failed to get database connection: " . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * ✅ Safe Query Execution with Exception Handling
     */
    public static function safeQuery(callable $query)
    {
        try {
            return $query(self::getInstance()->getPdo());
        } catch (\PDOException $e) {
            if (self::$logger) {
                self::$logger->error("❌ Database Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
            if ($e->getCode() == "23000") {
                return ApiHelper::sendJsonResponse('error', 'Duplicate entry error', [], 400);
            }
            return ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        } catch (\Exception $e) {
            if (self::$logger) {
                self::$logger->error("❌ Database Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
            return ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        }
    }

    /**
     * ✅ Wrapper for Insert Queries
     */
    public static function insert($table, $data, $useSecureDb = false)
    {
        return self::safeQuery(function ($pdo) use ($table, $data, $useSecureDb) {
            $dbInstance = $useSecureDb ? self::getSecureInstance() : self::getInstance();
            $pdo = $dbInstance->getPdo();
            $columns = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
            $stmt->execute(array_values($data));
            return $pdo->lastInsertId();
        });
    }

    /**
     * ✅ Wrapper for Update Queries
     */
    public static function update($table, $data, $where)
    {
        return self::safeQuery(function ($pdo) use ($table, $data, $where) {
            $set = implode(", ", array_map(fn($key) => "{$key} = ?", array_keys($data)));
            $whereClause = implode(" AND ", array_map(fn($key) => "{$key} = ?", array_keys($where)));
            $stmt = $pdo->prepare("UPDATE {$table} SET {$set} WHERE {$whereClause}");
            $stmt->execute(array_merge(array_values($data), array_values($where)));
            return $stmt->rowCount();
        });
    }

    /**
     * ✅ Wrapper for Delete Queries
     */
    public static function delete($table, $where)
    {
        return self::safeQuery(function ($pdo) use ($table, $where) {
            $whereClause = implode(" AND ", array_map(fn($key) => "{$key} = ?", array_keys($where)));
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$whereClause}");
            $stmt->execute(array_values($where));
            return $stmt->rowCount();
        });
    }

    /**
     * ✅ Wrapper for Select Queries
     */
    public static function select($query, $params = [])
    {
        return self::safeQuery(function ($pdo) use ($query, $params) {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        });
    }
}
