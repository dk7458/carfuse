<?php

namespace App\Models;

use App\Services\DatabaseHelper;
use App\Services\AuditService;

/**
 * Admin Model - Manages system administrators.
 */
class Admin extends BaseModel
{
    protected $table = 'admins';
    protected $resourceName = 'admin';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    
    /**
     * Constructor
     *
     * @param DatabaseHelper $dbHelper
     * @param AuditService|null $auditService
     */
    public function __construct(DatabaseHelper $dbHelper, AuditService $auditService = null)
    {
        parent::__construct($dbHelper, $auditService);
    }
    
    /**
     * Hash a password.
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password.
     *
     * @param string $plainPassword
     * @param string $hashedPassword
     * @return bool
     */
    public static function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }
    
    /**
     * Create an admin.
     * Override to handle password hashing.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = self::hashPassword($data['password']);
        }
        
        $id = parent::create($data);
        
        // Add custom audit logging if needed
        if ($this->auditService) {
            $this->auditService->logEvent('admin', 'admin_created', [
                'id' => $id,
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'role' => $data['role'] ?? null
            ]);
        }
        
        return $id;
    }
    
    /**
     * Update an admin.
     * Override to handle password hashing.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = self::hashPassword($data['password']);
        }
        
        $result = parent::update($id, $data);
        
        // Add custom audit logging if needed
        if ($result && $this->auditService) {
            $this->auditService->logEvent('admin', 'admin_updated', [
                'id' => $id,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get admin by email.
     *
     * @param string $email
     * @return array|null
     */
    public function getByEmail(string $email): ?array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE email = :email
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Restore a soft deleted admin.
     *
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool
    {
        if (!$this->useSoftDeletes) {
            return false;
        }
        
        $query = "UPDATE {$this->table} SET deleted_at = NULL WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $result = $stmt->execute([':id' => $id]);
        
        if ($result && $this->auditService) {
            $this->auditService->logEvent('admin', 'admin_restored', [
                'admin_id' => $id
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get users managed by this admin.
     *
     * @param int $adminId
     * @return array
     */
    public function getManagedUsers(int $adminId): array
    {
        $query = "
            SELECT * FROM users
            WHERE managed_by = :admin_id
            ORDER BY name ASC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
