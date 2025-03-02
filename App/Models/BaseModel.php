<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
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
     * @var PDO PDO instance
     */
    protected $pdo;

    /**
     * Constructor
     *
     * @param DatabaseHelper|null $dbHelper
     * @param AuditService|null $auditService
     */
    public function __construct(DatabaseHelper $dbHelper = null, AuditService $auditService = null)
    {
        $this->dbHelper = $dbHelper ?? new DatabaseHelper();
        $this->auditService = $auditService;
        $this->pdo = $this->dbHelper->getPdo();
    }

    /**
     * Find a record by ID
     *
     * @param int $id
     * @return array|null
     */
    public function find(int|string $id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query, [':id' => $id]);
        return $result ? $result[0] : null;
    }

    /**
     * Get all records from the table
     *
     * @return array
     */
    public function all(): array
    {
        $query = "SELECT * FROM {$this->table}";
        
        if ($this->useSoftDeletes) {
            $query .= " WHERE deleted_at IS NULL";
        }
        
        return $this->dbHelper->select($query);
    }

    /**
     * Create a new record
     *
     * @param array $data
     * @return int The ID of the created record
     */
    public function create(array $data): int
    {
        if ($this->useTimestamps && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        if ($this->useTimestamps && !isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        if ($this->useUuid && !isset($data['id'])) {
            $data['id'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
        }
        
        $id = $this->dbHelper->insert($this->table, $data);
        
        // Log audit event if service is available
        if ($this->auditService && $this->resourceName) {
            $this->auditService->logEvent($this->resourceName, 'create', [
                'id' => $id,
                'data' => $data
            ]);
        }
        
        return $id;
    }

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        if ($this->useTimestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $conditions = ['id' => $id];
        
        if ($this->useSoftDeletes) {
            $conditions['deleted_at IS NULL'] = null;
        }
        
        $result = $this->dbHelper->update($this->table, $data, $conditions);
        
        // Log audit event if service is available
        if ($result && $this->auditService && $this->resourceName) {
            $this->auditService->logEvent($this->resourceName, 'update', [
                'id' => $id,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }

    /**
     * Delete a record (soft delete if enabled)
     *
     * @param int $id
     * @return bool
     */
    public function delete(int|string $id): bool
    {
        if ($this->useSoftDeletes) {
            $data = ['deleted_at' => date('Y-m-d H:i:s')];
            $result = $this->dbHelper->update($this->table, $data, ['id' => $id]);
        } else {
            $result = $this->dbHelper->delete($this->table, ['id' => $id]);
        }
        
        // Log audit event if service is available
        if ($result && $this->auditService && $this->resourceName) {
            $this->auditService->logEvent($this->resourceName, 'delete', [
                'id' => $id
            ]);
        }
        
        return $result;
    }
}
