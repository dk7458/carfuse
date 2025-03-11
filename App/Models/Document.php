<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
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
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'file_path',
        'user_id',
        'type'
    ];

    /**
     * @var array Data type casting definitions
     */
    protected $casts = [
        'user_id' => 'int'
    ];

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
}
