<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

/**
 * Base Model
 *
 * Provides common functionality for all models.
 */
abstract class BaseModel
{
    protected $dbHelper;
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
        
        $result = $this->dbHelper->select($query, [':id' => $id]);
        return $result[0] ?? null; // Return first result or null
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
        
        return $this->dbHelper->select($query);
    }
    
    /**
     * Create a new record.
     *
     * @param array $data
     * @return int ID of the created record
     */
    public function create(array $data): int
    {
        if ($this->useTimestamps) {
            $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $id = $this->dbHelper->insert($this->table, $data);
        
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
        if ($this->useTimestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $result = $this->dbHelper->update($this->table, $data, ['id' => $id, 'deleted_at IS NULL']);
        
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
        $data = $this->useSoftDeletes ? ['deleted_at' => date('Y-m-d H:i:s')] : [];
        $result = $this->dbHelper->update($this->table, $data, ['id' => $id]);
        
        // Log audit if service is available and delete was successful
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, "Deleted {$this->resourceName}", [
                "{$this->resourceName}_id" => $id
            ]);
        }
        
        return $result;
    }
}
