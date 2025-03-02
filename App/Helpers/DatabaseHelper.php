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
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

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
    
        if (!isset($config['secure_database']) || !isset($config['app_database'])) {
            throw new Exception("Database configuration is missing required keys.");
        }
    
        return $type === 'secure' ? $config['secure_database'] : $config['app_database'];
    }
    
    public static function getInstance(): DatabaseHelper
    {
        if (self::$instance === null) {
            if (!isset(self::$logger)) {
                throw new Exception("Logger must be set before initializing the database.");
            }
            try {
                $dbConfig = self::getDatabaseConfig('default');
                self::$instance = new DatabaseHelper($dbConfig);
                self::$logger->info("✅ Application database initialized successfully.");
            } catch (Exception $e) {
                self::$logger->critical("❌ Application database initialization failed: " . $e->getMessage());
                die("Application database initialization failed.");
            }
        }
    
        return self::$instance;
    }
    
    public static function getSecureInstance(): DatabaseHelper
    {
        if (self::$secureInstance === null) {
            if (!isset(self::$logger)) {
                throw new Exception("Logger must be set before initializing the database.");
            }
            try {
                $dbConfig = self::getDatabaseConfig('secure');
                self::$secureInstance = new DatabaseHelper($dbConfig);
                self::$logger->info("✅ Secure database initialized successfully.");
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

    public static function safeQuery(callable $query, string $queryDescription = 'Database Query', bool $useSecureDb = false)
    {
        try {
            $dbInstance = $useSecureDb ? self::getSecureInstance() : self::getInstance();
            $result = $query($dbInstance->getPdo());
            self::$logger->info("✅ {$queryDescription} executed successfully using " . ($useSecureDb ? "secure" : "application") . " database.");
            return $result;
        } catch (\PDOException $e) {
            self::$logger->error("❌ {$queryDescription} Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($e->getCode() == "23000") {
                return ApiHelper::sendJsonResponse('error', 'Duplicate entry error', [], 400);
            }
            return ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        } catch (\Exception $e) {
            self::$logger->error("❌ {$queryDescription} Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ApiHelper::sendJsonResponse('error', 'Database query error', [], 500);
        }
    }

    public static function insert(string $table, array $data, bool $useSecureDb = false): string
    {
        return self::safeQuery(function ($pdo) use ($table, $data) {
            $columns = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), "?"));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $lastInsertId = $pdo->lastInsertId();
            self::$logger->info("✅ Inserted into {$table} with ID {$lastInsertId}");
            return $lastInsertId;
        }, "Insert into {$table}", $useSecureDb);
    }

    public static function update(string $table, array $data, array $where, bool $useSecureDb = false): int
    {
        return self::safeQuery(function ($pdo) use ($table, $data, $where) {
            $set = implode(", ", array_map(fn($key) => "{$key} = ?", array_keys($data)));
            $whereClause = implode(" AND ", array_map(fn($key) => "{$key} = ?", array_keys($where)));
            $sql = "UPDATE {$table} SET {$set} WHERE {$whereClause}";
            $stmt = $pdo->prepare($sql);
            $params = array_merge(array_values($data), array_values($where));
            $stmt->execute($params);
            $rowCount = $stmt->rowCount();
            self::$logger->info("✅ Updated {$rowCount} rows in {$table}");
            return $rowCount;
        }, "Update {$table}", $useSecureDb);
    }

    public static function delete(string $table, array $where, bool $softDelete = false, bool $useSecureDb = false): int
    {
        return self::safeQuery(function ($pdo) use ($table, $where, $softDelete) {
            if ($softDelete) {
                $sql = "UPDATE {$table} SET deleted_at = NOW() WHERE " . implode(" AND ", array_map(fn($key) => "{$key} = ?", array_keys($where)));
            } else {
                $sql = "DELETE FROM {$table} WHERE " . implode(" AND ", array_map(fn($key) => "{$key} = ?", array_keys($where)));
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($where));
            $rowCount = $stmt->rowCount();
            self::$logger->info("✅ Deleted {$rowCount} rows from {$table}");
            return $rowCount;
        }, "Delete from {$table}", $useSecureDb);
    }

    public static function select(string $query, array $params = [], bool $useSecureDb = false): array
    {
        return self::safeQuery(function ($pdo) use ($query, $params) {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            self::$logger->info("✅ Selected " . count($results) . " rows using query: " . $query);
            return $results;
        }, "Select Query", $useSecureDb);
    }
}
