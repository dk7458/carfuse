<?php

namespace App\Helpers;

use PDO;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ApiHelper;

class DatabaseHelper
{
    protected static ?DatabaseHelper $instance = null;
    protected static ?DatabaseHelper $secureInstance = null;
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

    public static function getAppInstance(): ?DatabaseHelper
    {
        return self::$instance;
    }

    public static function getSecureDbInstance(): ?DatabaseHelper
    {
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
     * Execute a database query safely with comprehensive logging and error handling
     * 
     * @param callable $query Function containing the query to execute
     * @param string $queryDescription Description of the query for logging
     * @param bool $useSecureDb Whether to use the secure database
     * @param array $context Additional context information for logging
     * @return mixed Query result or error response
     */
    public static function safeQuery(
        callable $query, 
        string $queryDescription = 'Database Query', 
        bool $useSecureDb = false,
        array $context = []
    ) {
        $startTime = microtime(true);
        $dbInstance = $useSecureDb ? self::getSecureInstance() : self::getInstance();
        $dbType = $useSecureDb ? "secure" : "application";
        
        try {
            // Get database name for logging
            $databaseName = $dbInstance->getPdo()->query("SELECT DATABASE()")->fetchColumn();
            
            // Log query execution start with sanitized parameters
            $logContext = array_merge($context, [
                'database' => $databaseName,
                'database_type' => $dbType,
                'timestamp_start' => date('Y-m-d H:i:s.u'),
            ]);
            
            // Sanitize any sensitive data in context
            $sanitizedContext = self::sanitizeLogContext($logContext);
            self::$logger->info("🔍 Executing {$queryDescription} on {$dbType} database: {$databaseName}", $sanitizedContext);
            
            // Execute the query
            $result = $query($dbInstance->getPdo());
            
            // Calculate execution time
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log successful query completion
            self::$logger->info("✅ {$queryDescription} completed successfully", [
                'database' => $databaseName,
                'execution_time_ms' => $executionTime,
                'database_type' => $dbType,
                'timestamp_end' => date('Y-m-d H:i:s.u'),
            ]);
            
            return $result;
            
        } catch (\PDOException $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $errorCode = $e->getCode();
            
            // Log detailed error information
            self::$logger->error("❌ {$queryDescription} failed with PDO error {$errorCode}", [
                'error_message' => $e->getMessage(),
                'database_type' => $dbType,
                'execution_time_ms' => $executionTime,
                'error_code' => $errorCode,
                'trace' => $e->getTraceAsString(),
                'context' => $sanitizedContext ?? [],
            ]);
            
            // Return appropriate error responses based on error type
            if ($errorCode == "23000") {
                return ApiHelper::sendJsonResponse('error', 'Database constraint violation: Duplicate or invalid data', [], 400);
            } elseif ($errorCode == "42S02") {
                return ApiHelper::sendJsonResponse('error', 'Table not found error', [], 500);
            } elseif ($errorCode == "42000") {
                return ApiHelper::sendJsonResponse('error', 'SQL syntax error', [], 500);
            } else {
                return ApiHelper::sendJsonResponse('error', 'Database query failed: ' . self::getSafeErrorMessage($e->getMessage()), [], 500);
            }
            
        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log general exceptions
            self::$logger->error("❌ {$queryDescription} failed with exception", [
                'error_message' => $e->getMessage(),
                'database_type' => $dbType,
                'execution_time_ms' => $executionTime,
                'trace' => $e->getTraceAsString(),
                'context' => $sanitizedContext ?? [],
            ]);
            
            return ApiHelper::sendJsonResponse('error', 'Database operation failed: ' . self::getSafeErrorMessage($e->getMessage()), [], 500);
        }
    }

    /**
     * Sanitize log context to remove sensitive data
     */
    private static function sanitizeLogContext(array $context): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'credit_card', 'card_number', 'cvv'];
        
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = self::sanitizeLogContext($value);
            } elseif (is_string($value) && self::containsSensitiveData($key, $sensitiveKeys)) {
                $context[$key] = '***REDACTED***';
            }
        }
        
        return $context;
    }
    
    /**
     * Check if a key contains sensitive data
     */
    private static function containsSensitiveData(string $key, array $sensitiveKeys): bool
    {
        $key = strtolower($key);
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (strpos($key, $sensitiveKey) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get a safe error message that doesn't expose sensitive information
     */
    private static function getSafeErrorMessage(string $originalMessage): string
    {
        // Remove potentially sensitive details from error messages
        $safeMessage = preg_replace('/SQLSTATE\[\w+\]: .+?: /', '', $originalMessage);
        $safeMessage = preg_replace('/near \'(.+?)\'/', 'near [SQL]', $safeMessage);
        
        return $safeMessage;
    }

    /**
     * Insert data into a table
     * 
     * @param string $table Table name
     * @param array $data Data to insert (column => value)
     * @param bool $useSecureDb Whether to use the secure database
     * @param array $context Additional context information for logging
     * @return string|mixed Last insert ID or error response
     */
    public static function insert(
        string $table, 
        array $data, 
        bool $useSecureDb = false,
        array $context = []
    ): string {
        $queryContext = array_merge($context, [
            'operation' => 'INSERT',
            'table' => $table,
            'field_count' => count($data),
        ]);
        
        return self::safeQuery(function ($pdo) use ($table, $data) {
            $columns = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), "?"));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $lastInsertId = $pdo->lastInsertId();
            
            return $lastInsertId;
        }, "Insert into {$table}", $useSecureDb, $queryContext);
    }

    /**
     * Update data in a table
     * 
     * @param string $table Table name
     * @param array $data Data to update (column => value)
     * @param array $where Where conditions (column => value)
     * @param bool $useSecureDb Whether to use the secure database 
     * @param array $context Additional context information for logging
     * @return int|mixed Number of affected rows or error response
     */
    public static function update(
        string $table, 
        array $data, 
        array $where, 
        bool $useSecureDb = false,
        array $context = []
    ): int {
        $queryContext = array_merge($context, [
            'operation' => 'UPDATE',
            'table' => $table,
            'field_count' => count($data),
            'condition_count' => count($where),
        ]);
        
        return self::safeQuery(function ($pdo) use ($table, $data, $where) {
            $set = implode(", ", array_map(fn($key) => "{$key} = ?", array_keys($data)));
            $whereClause = implode(" AND ", array_map(fn($key) => "{$key} = ?", array_keys($where)));
            $sql = "UPDATE {$table} SET {$set} WHERE {$whereClause}";
            
            $stmt = $pdo->prepare($sql);
            $params = array_merge(array_values($data), array_values($where));
            $stmt->execute($params);
            $rowCount = $stmt->rowCount();
            
            // Log affected rows count
            self::$logger->info("Updated {$rowCount} rows in table {$table}");
            
            return $rowCount;
        }, "Update {$table}", $useSecureDb, $queryContext);
    }

    /**
     * Delete data from a table
     * 
     * @param string $table Table name
     * @param array $where Where conditions (column => value)
     * @param bool $softDelete Whether to perform a soft delete
     * @param bool $useSecureDb Whether to use the secure database
     * @param array $context Additional context information for logging
     * @return int|mixed Number of affected rows or error response
     */
    public static function delete(
        string $table, 
        array $where, 
        bool $softDelete = false, 
        bool $useSecureDb = false,
        array $context = []
    ): int {
        $operation = $softDelete ? 'SOFT_DELETE' : 'DELETE';
        $queryContext = array_merge($context, [
            'operation' => $operation,
            'table' => $table,
            'condition_count' => count($where),
        ]);
        
        return self::safeQuery(function ($pdo) use ($table, $where, $softDelete) {
            if ($softDelete) {
                $sql = "UPDATE {$table} SET deleted_at = NOW() WHERE " . implode(" AND ", array_map(fn($key) => "{$key} = ?", array_keys($where)));
            } else {
                $sql = "DELETE FROM {$table} WHERE " . implode(" AND ", array_map(fn($key) => "{$key} = ?", array_keys($where)));
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($where));
            $rowCount = $stmt->rowCount();
            
            // Log affected rows count
            self::$logger->info(($softDelete ? "Soft deleted" : "Deleted") . " {$rowCount} rows from table {$table}");
            
            return $rowCount;
        }, ($softDelete ? "Soft delete from" : "Delete from") . " {$table}", $useSecureDb, $queryContext);
    }

    /**
     * Select data from the database
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param bool $useSecureDb Whether to use the secure database
     * @param array $context Additional context information for logging
     * @return array|mixed Query results or error response
     */
    public static function select(
        string $query, 
        array $params = [], 
        bool $useSecureDb = false,
        array $context = []
    ): array {
        $queryContext = array_merge($context, [
            'operation' => 'SELECT',
            'param_count' => count($params),
            'query_hash' => md5($query), // For tracking unique queries
        ]);
        
        return self::safeQuery(function ($pdo) use ($query, $params) {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            // Log result count
            self::$logger->debug("Query returned " . count($results) . " rows");
            
            return $results;
        }, "Select query", $useSecureDb, $queryContext);
    }
    
    /**
     * Execute a raw query with enhanced logging
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param bool $useSecureDb Whether to use the secure database
     * @param array $context Additional context information for logging
     * @return mixed Query result or error response
     */
    public static function rawQuery(
        string $query, 
        array $params = [], 
        bool $useSecureDb = false,
        array $context = []
    ) {
        $operation = strtoupper(trim(explode(' ', $query)[0]));
        $queryContext = array_merge($context, [
            'operation' => $operation,
            'param_count' => count($params),
            'query_hash' => md5($query),
        ]);
        
        return self::safeQuery(function ($pdo) use ($query, $params, $operation) {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            if ($operation === 'SELECT') {
                $results = $stmt->fetchAll();
                return $results;
            } else {
                $rowCount = $stmt->rowCount();
                self::$logger->debug("{$operation} affected {$rowCount} rows");
                return $rowCount;
            }
        }, "{$operation} raw query", $useSecureDb, $queryContext);
    }
}
