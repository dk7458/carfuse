<?php

namespace App\Models;

use App\Services\DatabaseHelper;
use App\Services\AuditService;

/**
 * Document Model
 *
 * Represents documents stored in the system and provides methods
 * for managing and querying them.
 */
class Document extends BaseModel
{
    protected $table = 'documents';
    protected $resourceName = 'document';
    protected $useSoftDeletes = false; // Document model doesn't use soft deletes

    /**
     * Create a new document record.
     *
     * @param array $data Data including name, file_path, user_id, type
     * @return int The ID of the newly created document.
     */
    public function create(array $data): int
    {
        // Add created_at if using timestamps but not provided
        if ($this->useTimestamps && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        $id = parent::create($data);
        
        // Add custom audit logging if needed
        if ($this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'document_created', [
                'id' => $id,
                'name' => $data['name'] ?? null,
                'type' => $data['type'] ?? null,
                'user_id' => $data['user_id'] ?? null
            ]);
        }
        
        return $id;
    }

    /**
     * Override find to add audit logging for views.
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        $document = parent::find($id);
        
        // Log view event if document was found
        if ($document && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'document_viewed', [
                'id' => $id,
                'name' => $document['name'] ?? 'unknown'
            ]);
        }
        
        return $document;
    }

    /**
     * Retrieve documents associated with a user.
     *
     * @param int $userId The ID of the user.
     * @return array A list of documents associated with the user.
     */
    public function getByUserId(int $userId): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retrieve documents by type.
     *
     * @param string $type The type of document (e.g., 'contract', 'terms').
     * @return array A list of documents matching the specified type.
     */
    public function getByType(string $type): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE type = :type 
            ORDER BY created_at DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Delete a document by its ID.
     *
     * @param int $id The ID of the document to delete.
     * @return bool True on success, false otherwise.
     */
    public function delete(int $id): bool
    {
        // First, get document details for audit log
        $document = $this->find($id);
        
        if (!$document) {
            return false;
        }
        
        $result = parent::delete($id);
        
        // Add custom audit log if needed
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'document_deleted', [
                'id' => $id,
                'name' => $document['name'] ?? 'unknown',
                'type' => $document['type'] ?? null
            ]);
        }
        
        return $result;
    }
    
    /**
     * Update a document's details.
     *
     * @param int $id The ID of the document to update.
     * @param array $data The data to update.
     * @return bool True on success, false otherwise.
     */
    public function update(int $id, array $data): bool
    {
        // Filter data to only include allowed fields
        $validData = array_filter($data, function($key) {
            return in_array($key, ['name', 'file_path', 'type']);
        }, ARRAY_FILTER_USE_KEY);
        
        if (empty($validData)) {
            return false;
        }
        
        $result = parent::update($id, $validData);
        
        // Add custom audit log if needed
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'document_updated', [
                'id' => $id,
                'updated_fields' => array_keys($validData)
            ]);
        }
        
        return $result;
    }
}
