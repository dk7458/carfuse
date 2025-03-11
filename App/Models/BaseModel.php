<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use PDO;

/**
 * BaseModel
 * 
 * Base class for all models that use the DatabaseHelper instead of Eloquent
 */
abstract class BaseModel
{
    /**
     * @var string The table associated with the model
     */
    protected $table;
    
    /**
     * @var string The name of the resource for audit logging
     */
    protected $resourceName;
    
    /**
     * @var bool Whether the model uses timestamps
     */
    protected $useTimestamps = true;
    
    /**
     * @var bool Whether the model uses soft deletes
     */
    protected $useSoftDeletes = false;
    
    /**
     * @var bool Whether the model uses UUID as primary key
     */
    protected $useUuid = false;
    
    /**
     * @var DatabaseHelper Database helper instance
     */
    protected $dbHelper;
    
    /**
     * @var AuditService|null Audit service instance
     */
    protected $auditService;
    
    /**
     * @var LoggerInterface|null Logger instance
     */
    protected $logger;
    
    /**
     * @var PDO PDO instance
     */
    protected $pdo;

    /**
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [];
    
    /**
     * @var array Data type casting definitions
     */
    protected $casts = [];

    /**
     * Constructor
     *
     * @param DatabaseHelper $dbHelper Database helper instance
     * @param AuditService|null $auditService Audit service instance for logging (optional)
     * @param LoggerInterface|null $logger Logger for errors and debug info (optional)
     */
    public function __construct(
        DatabaseHelper $dbHelper, 
        ?AuditService $auditService = null, 
        ?LoggerInterface $logger = null)
    {
        $this->dbHelper = $dbHelper;
        $this->auditService = $auditService;
        $this->logger = $logger;
        $this->pdo = $this->dbHelper->getPdo();
        
        if (!$this->table) {
            throw new \RuntimeException("Table name must be defined in the model class");
        }
    }

    /**
     * Find a record by ID
     *
     * @param int|string $id
     * @return array|null
     */
    public function find(int|string $id): ?array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id";
            
            if ($this->useSoftDeletes) {
                $query .= " AND deleted_at IS NULL";
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in find(): " . $e->getMessage(), ['id' => $id, 'table' => $this->table]);
            }
            throw $e;
        }
    }

    /**
     * Find records by a field value
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $operator Comparison operator (=, >, <, etc.)
     * @return array
     */
    public function findBy(string $field, $value, string $operator = '='): array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE {$field} {$operator} :value";
            
            if ($this->useSoftDeletes) {
                $query .= " AND deleted_at IS NULL";
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':value', $value);
            $stmt->execute();
            
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in findBy(): " . $e->getMessage(), [
                    'field' => $field,
                    'value' => $value,
                    'table' => $this->table
                ]);
            }
            throw $e;
        }
    }

    /**
     * Find a single record by a field value
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @return array|null
     */
    public function findOneBy(string $field, $value): ?array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE {$field} = :value";
            
            if ($this->useSoftDeletes) {
                $query .= " AND deleted_at IS NULL";
            }
            
            $query .= " LIMIT 1";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':value', $value);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in findOneBy(): " . $e->getMessage(), [
                    'field' => $field,
                    'value' => $value,
                    'table' => $this->table
                ]);
            }
            throw $e;
        }
    }

    /**
     * Get all records from the table
     *
     * @param array $orderBy Optional ordering ['field' => 'ASC|DESC']
     * @param int|null $limit Optional record limit
     * @param int|null $offset Optional offset for pagination
     * @return array
     */
    public function all(array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        try {
            $query = "SELECT * FROM {$this->table}";
            
            if ($this->useSoftDeletes) {
                $query .= " WHERE deleted_at IS NULL";
            }
            
            // Add ordering
            if (!empty($orderBy)) {
                $query .= " ORDER BY ";
                $orders = [];
                foreach ($orderBy as $field => $direction) {
                    $orders[] = "{$field} {$direction}";
                }
                $query .= implode(', ', $orders);
            }
            
            // Add limit and offset
            if ($limit !== null) {
                $query .= " LIMIT {$limit}";
                if ($offset !== null) {
                    $query .= " OFFSET {$offset}";
                }
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in all(): " . $e->getMessage(), ['table' => $this->table]);
            }
            throw $e;
        }
    }

    /**
     * Create a new record
     *
     * @param array $data
     * @return int|string The ID of the created record
     */
    public function create(array $data): int|string
    {
        try {
            // Filter data to only include fillable fields
            if (!empty($this->fillable)) {
                $data = array_intersect_key($data, array_flip($this->fillable));
            }
            
            // Add timestamps
            if ($this->useTimestamps) {
                $now = date('Y-m-d H:i:s');
                if (!isset($data['created_at'])) {
                    $data['created_at'] = $now;
                }
                if (!isset($data['updated_at'])) {
                    $data['updated_at'] = $now;
                }
            }
            
            // Generate UUID if needed
            if ($this->useUuid && !isset($data['id'])) {
                $data['id'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
            }
            
            // Create placeholders
            $fields = array_keys($data);
            $placeholders = array_map(function($field) {
                return ":{$field}";
            }, $fields);
            
            $query = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                     VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($query);
            
            // Bind values with appropriate types based on casts
            foreach ($data as $field => $value) {
                $type = PDO::PARAM_STR;
                if (isset($this->casts[$field])) {
                    if (in_array($this->casts[$field], ['int', 'integer', 'timestamp'])) {
                        $type = PDO::PARAM_INT;
                    } elseif (in_array($this->casts[$field], ['bool', 'boolean'])) {
                        $type = PDO::PARAM_BOOL;
                    }
                }
                $stmt->bindValue(":{$field}", $value, $type);
            }
            
            $stmt->execute();
            
            // Get the ID of the inserted record
            $id = $this->useUuid && isset($data['id']) ? $data['id'] : $this->pdo->lastInsertId();
            
            // Log audit event if service is available
            if ($this->auditService && $this->resourceName) {
                $this->auditService->logEvent(
                    "{$this->resourceName}_created",
                    "{$this->resourceName} record created",
                    ['id' => $id, 'data' => $data],
                    $data['user_id'] ?? null,
                    $id,
                    $this->resourceName
                );
            }
            
            return $id;
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in create(): " . $e->getMessage(), [
                    'data' => $data,
                    'table' => $this->table
                ]);
            }
            throw $e;
        }
    }

    /**
     * Update a record
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        try {
            // Filter data to only include fillable fields
            if (!empty($this->fillable)) {
                $data = array_intersect_key($data, array_flip($this->fillable));
            }
            
            // Add updated_at timestamp
            if ($this->useTimestamps && !isset($data['updated_at'])) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            // Build SET clause
            $setClauses = [];
            foreach ($data as $field => $value) {
                $setClauses[] = "{$field} = :{$field}";
            }
            
            $query = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id = :id";
            
            if ($this->useSoftDeletes) {
                $query .= " AND deleted_at IS NULL";
            }
            
            $stmt = $this->pdo->prepare($query);
            
            // Bind values
            $stmt->bindValue(':id', $id);
            foreach ($data as $field => $value) {
                $type = PDO::PARAM_STR;
                if (isset($this->casts[$field])) {
                    if (in_array($this->casts[$field], ['int', 'integer', 'timestamp'])) {
                        $type = PDO::PARAM_INT;
                    } elseif (in_array($this->casts[$field], ['bool', 'boolean'])) {
                        $type = PDO::PARAM_BOOL;
                    }
                }
                $stmt->bindValue(":{$field}", $value, $type);
            }
            
            $stmt->execute();
            
            $affected = $stmt->rowCount();
            
            // Log audit event if service is available
            if ($affected > 0 && $this->auditService && $this->resourceName) {
                $this->auditService->logEvent(
                    "{$this->resourceName}_updated",
                    "{$this->resourceName} record updated",
                    ['id' => $id, 'updated_fields' => array_keys($data)],
                    $data['user_id'] ?? null, 
                    $id,
                    $this->resourceName
                );
            }
            
            return $affected > 0;
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in update(): " . $e->getMessage(), [
                    'id' => $id,
                    'data' => $data,
                    'table' => $this->table
                ]);
            }
            throw $e;
        }
    }

    /**
     * Delete a record (soft delete if enabled)
     *
     * @param int|string $id
     * @return bool
     */
    public function delete(int|string $id): bool
    {
        try {
            if ($this->useSoftDeletes) {
                // Soft delete - update the deleted_at field
                $query = "UPDATE {$this->table} SET deleted_at = :deleted_at WHERE id = :id AND deleted_at IS NULL";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':deleted_at', date('Y-m-d H:i:s'));
                $stmt->bindValue(':id', $id);
            } else {
                // Hard delete - remove the record completely
                $query = "DELETE FROM {$this->table} WHERE id = :id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':id', $id);
            }
            
            $stmt->execute();
            
            $affected = $stmt->rowCount();
            
            // Log audit event if service is available
            if ($affected > 0 && $this->auditService && $this->resourceName) {
                $eventType = $this->useSoftDeletes ? "{$this->resourceName}_soft_deleted" : "{$this->resourceName}_deleted";
                $this->auditService->logEvent(
                    $eventType,
                    "{$this->resourceName} record deleted",
                    ['id' => $id, 'soft_delete' => $this->useSoftDeletes],
                    null,
                    $id,
                    $this->resourceName
                );
            }
            
            return $affected > 0;
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in delete(): " . $e->getMessage(), [
                    'id' => $id, 
                    'table' => $this->table
                ]);
            }
            throw $e;
        }
    }

    /**
     * Get validation rules for the model
     *
     * @return array
     */
    public static function validationRules(): array
    {
        return [];
    }

    /**
     * Execute a raw SQL query
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for the query
     * @param bool $fetchAll Whether to fetch all results or just one
     * @return array|null Query results
     */
    protected function rawQuery(string $query, array $params = [], bool $fetchAll = true): ?array
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            if ($fetchAll) {
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                return $result ?: [];
            } else {
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                return $result ?: null;
            }
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Database error in rawQuery(): " . $e->getMessage(), [
                    'query' => $query,
                    'params' => $params
                ]);
            }
            throw $e;
        }
    }
}
