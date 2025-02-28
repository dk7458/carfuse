<?php

namespace App\Models;

use App\Services\DatabaseHelper;
use App\Services\AuditService;

/**
 * Base Model
 *
 * Provides common functionality for all models.
 */
abstract class BaseModel
{
    protected $dbHelper;
    protected $pdo;
    protected $auditService;
    
    // The table associated with the model
    protected $table;
    
    // Whether to use timestamps (created_at, updated_at)
    protected $useTimestamps = true;
    
    // Whether to use soft deletes (deleted_at)
    protected $useSoftDeletes = true;
    
    // The model's resource name for auditing
    protected $resourceName;
    
    /**
     * Constructor
     *
     * @param DatabaseHelper $dbHelper
     * @param AuditService|null $auditService
     */
    public function __construct(DatabaseHelper $dbHelper, AuditService $auditService = null)
    {
        $this->dbHelper = $dbHelper;
        $this->pdo = $dbHelper->getPdo();
        $this->auditService = $auditService;
        
        if (!$this->table) {
            throw new \Exception("No table defined for " . get_class($this));
        }
        
        if (!$this->resourceName) {
            // Default resource name from class name
            $className = (new \ReflectionClass($this))->getShortName();
            $this->resourceName = strtolower($className);
        }
    }
    
    /**
     * Find a record by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get all records.
     *
     * @return array
     */
    public function all(): array
    {
        $query = "SELECT * FROM {$this->table}";
        
        if ($this->useSoftDeletes) {
            $query .= " WHERE deleted_at IS NULL";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Create a new record.
     *
     * @param array $data
     * @return int ID of the created record
     */
    public function create(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = [];
        $params = [];
        
        foreach ($fields as $field) {
            $placeholders[] = ":{$field}";
            $params[":{$field}"] = $data[$field];
        }
        
        if ($this->useTimestamps) {
            $fields[] = 'created_at';
            $placeholders[] = 'NOW()';
            $fields[] = 'updated_at';
            $placeholders[] = 'NOW()';
        }
        
        $fieldsSql = implode(', ', $fields);
        $placeholdersSql = implode(', ', $placeholders);
        
        $query = "INSERT INTO {$this->table} ({$fieldsSql}) VALUES ({$placeholdersSql})";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $id = $this->pdo->lastInsertId();
        
        // Log audit if service is available
        if ($this->auditService) {
            $this->auditService->logEvent($this->resourceName, "Created {$this->resourceName}", [
                "{$this->resourceName}_id" => $id,
                'data' => $data
            ]);
        }
        
        return $id;
    }
    
    /**
     * Update a record.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }
        
        $setClauses = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        if ($this->useTimestamps) {
            $setClauses[] = "updated_at = NOW()";
        }
        
        $setClause = implode(', ', $setClauses);
        
        $query = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $stmt = $this->pdo->prepare($query);
        $result = $stmt->execute($params);
        
        // Log audit if service is available and update was successful
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, "Updated {$this->resourceName}", [
                "{$this->resourceName}_id" => $id,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }
    
    /**
     * Soft delete a record.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        if (!$this->useSoftDeletes) {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $result = $stmt->execute([':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL");
            $result = $stmt->execute([':id' => $id]);
        }
        
        // Log audit if service is available and delete was successful
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, "Deleted {$this->resourceName}", [
                "{$this->resourceName}_id" => $id
            ]);
        }
        
        return $result;
    }
}
